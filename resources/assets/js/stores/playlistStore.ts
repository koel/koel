import { differenceBy, orderBy, pick } from 'lodash'
import { reactive } from 'vue'
import { arrayify, moveItemsInList } from '@/utils/helpers'
import { logger } from '@/utils/logger'
import { uuid } from '@/utils/crypto'
import { http } from '@/services/http'
import { cache } from '@/services/cache'
import models from '@/config/smart-playlist/models'
import operators from '@/config/smart-playlist/operators'
import { playableStore } from '@/stores/playableStore'

interface CreatePlaylistRequestData {
  name: Playlist['name']
  songs: Playable['id'][]
  description: Playlist['description']
  folder_id: PlaylistFolder['id'] | null
  rules?: SmartPlaylistRuleGroup[]
}

export type CreatePlaylistData = Pick<Playlist, 'name' | 'description' | 'folder_id' | 'cover'> & {
  songs?: Playable['id'][]
  rules?: SmartPlaylistRuleGroup[]
}

export interface UpdatePlaylistData {
  name: Playlist['name']
  description: Playlist['description']
  folder_id?: PlaylistFolder['id'] | null
  cover?: string | null
  rules?: SmartPlaylistRuleGroup[]
}

export const playlistStore = {
  state: reactive({
    playlists: [] as Playlist[],
  }),

  init (playlists: Playlist[]) {
    this.sort(reactive(playlists)).forEach(playlist => {
      if (!playlist.is_smart) {
        this.state.playlists.push(playlist)
      } else {
        try {
          this.setupSmartPlaylist(playlist)
          this.state.playlists.push(playlist)
        } catch (error: unknown) {
          logger.warn(`Failed to setup smart playlist "${playlist.name}".`, error)
        }
      }
    })
  },

  /**
   * Set up a smart playlist by properly construct its structure from serialized database values.
   */
  setupSmartPlaylist: (playlist: Playlist) => {
    playlist.rules.forEach(group => {
      group.rules.forEach(rule => {
        const serializedRule = rule as unknown as SerializedSmartPlaylistRule
        const model = models.find(model => model.name === serializedRule.model)

        if (!model) {
          logger.error(`Invalid model ${rule.model} found in smart playlist ${playlist.name} (ID ${playlist.id})`)
          return
        }

        rule.model = model
      })
    })
  },

  byId (id: Playlist['id']) {
    return this.state.playlists.find(playlist => playlist.id === id)
  },

  byFolder (folder: PlaylistFolder) {
    return this.state.playlists.filter(({ folder_id }) => folder_id === folder.id)
  },

  async store (
    data: Pick<Playlist, 'name' | 'description' | 'folder_id'> & { rules?: SmartPlaylistRuleGroup[] },
    songs: Playable[] = [],
  ) {
    const requestData: CreatePlaylistRequestData = {
      ...pick(data, 'name', 'description', 'folder_id'),
      songs: songs.map(song => song.id),
    }

    // Reformat the rules to be database-ready.
    if (data.rules) {
      requestData.rules = this.serializeSmartPlaylistRulesForStorage(data.rules)
    }

    const playlist = reactive(await http.post<Playlist>('playlists', requestData))

    if (playlist.is_smart) {
      this.setupSmartPlaylist(playlist)
    }

    this.state.playlists.push(playlist)
    this.state.playlists = this.sort(this.state.playlists)

    return playlist
  },

  async delete (playlist: Playlist) {
    await http.delete(`playlists/${playlist.id}`)
    this.state.playlists = differenceBy(this.state.playlists, [playlist], 'id')
  },

  async addContent (playlist: Playlist, playables: Playable[]) {
    if (playlist.is_smart) {
      return playlist
    }

    const updatedPlayables = await http.post<Playable[]>(`playlists/${playlist.id}/songs`, {
      songs: playables.map(song => song.id),
    })

    playableStore.syncWithVault(updatedPlayables)
    cache.remove(['playlist.songs', playlist.id])

    return playlist
  },

  removeContent: async (playlist: Playlist, playables: Playable[]) => {
    if (playlist.is_smart) {
      return playlist
    }

    await http.delete(`playlists/${playlist.id}/songs`, { songs: playables.map(song => song.id) })
    cache.remove(['playlist.songs', playlist.id])

    return playlist
  },

  async update (playlist: Playlist, data: UpdatePlaylistData) {
    await http.put(`playlists/${playlist.id}`, {
      ...data,
      rules: data.rules ? this.serializeSmartPlaylistRulesForStorage(data.rules) : null,
    })

    if (playlist.is_smart) {
      cache.remove(['playlist.songs', playlist.id])
    }

    Object.assign(this.byId(playlist.id)!, data)
  },

  createEmptySmartPlaylistRule: (): SmartPlaylistRule => ({
    id: uuid(),
    model: models[0],
    operator: operators[0].operator,
    value: [''],
  }),

  createEmptySmartPlaylistRuleGroup (): SmartPlaylistRuleGroup {
    return {
      id: uuid(),
      rules: [this.createEmptySmartPlaylistRule()],
    }
  },

  /**
   * Serialize the rule (groups) to be storage-ready.
   */
  serializeSmartPlaylistRulesForStorage: (ruleGroups: SmartPlaylistRuleGroup[]) => {
    if (!ruleGroups || !ruleGroups.length) {
      return null
    }

    const serializedGroups = JSON.parse(JSON.stringify(ruleGroups))

    serializedGroups.forEach((group: any): void => {
      group.rules.forEach((rule: any) => {
        rule.model = rule.model.name
      })
    })

    return serializedGroups
  },

  sort: (playlists: Playlist[]) => {
    return orderBy(playlists, ['is_smart', 'name'], ['desc', 'asc'])
  },

  moveItemsInPlaylist: async (
    playlist: Playlist,
    playables: MaybeArray<Playable>,
    target: Playable,
    placement: Placement,
  ) => {
    const orderHash = JSON.stringify(playlist.playables?.map(({ id }) => id))
    playlist.playables?.splice(
      0,
      playlist.playables.length,
      ...moveItemsInList(playlist.playables, playables, target, placement),
    )

    if (orderHash !== JSON.stringify(playlist.playables?.map(({ id }) => id))) {
      await http.silently.post(`playlists/${playlist.id}/songs/move`, {
        placement,
        songs: arrayify(playables).map(({ id }) => id),
        target: target.id,
      })
    }
  },

  async removeCover (playlist: Playlist) {
    playlist.cover = null
    await http.delete(`playlists/${playlist.id}/cover`)
  },
}
