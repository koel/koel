import { differenceBy, orderBy } from 'lodash'
import { reactive } from 'vue'
import { arrayify, logger, moveItemsInList, uuid } from '@/utils'
import { cache, http } from '@/services'
import models from '@/config/smart-playlist/models'
import operators from '@/config/smart-playlist/operators'
import { songStore } from '@/stores/songStore'

type CreatePlaylistRequestData = {
  name: Playlist['name']
  songs: Playable['id'][]
  rules?: SmartPlaylistRuleGroup[]
  folder_id?: PlaylistFolder['name']
  own_songs_only?: boolean
}

export const playlistStore = {
  state: reactive({
    playlists: [] as Playlist[]
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
    name: string,
    data: Partial<Pick<Playlist, 'rules' | 'folder_id' | 'own_songs_only'>> = {},
    songs: Playable[] = []
  ) {
    const requestData: CreatePlaylistRequestData = {
      name,
      songs: songs.map(song => song.id)
    }

    data.rules && (requestData.rules = this.serializeSmartPlaylistRulesForStorage(data.rules))
    data.folder_id && (requestData.folder_id = data.folder_id)
    data.own_songs_only && (requestData.own_songs_only = data.own_songs_only)

    const playlist = reactive(await http.post<Playlist>('playlists', requestData))

    playlist.is_smart && this.setupSmartPlaylist(playlist)

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
      songs: playables.map(song => song.id)
    })

    songStore.syncWithVault(updatedPlayables)
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

  async update (playlist: Playlist, data: Partial<Pick<Playlist, 'name' | 'rules' | 'folder_id' | 'own_songs_only'>>) {
    await http.put(`playlists/${playlist.id}`, {
      name: data.name,
      rules: data.rules ? this.serializeSmartPlaylistRulesForStorage(data.rules) : null,
      folder_id: data.folder_id,
      own_songs_only: data.own_songs_only
    })

    playlist.is_smart && cache.remove(['playlist.songs', playlist.id])
    Object.assign(this.byId(playlist.id)!, data)
  },

  createEmptySmartPlaylistRule: (): SmartPlaylistRule => ({
    id: uuid(),
    model: models[0],
    operator: operators[0].operator,
    value: ['']
  }),

  createEmptySmartPlaylistRuleGroup (): SmartPlaylistRuleGroup {
    return {
      id: uuid(),
      rules: [this.createEmptySmartPlaylistRule()]
    }
  },

  /**
   * Serialize the rule (groups) to be ready for database.
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

  moveItemsInPlaylist: async (playlist: Playlist, songs: MaybeArray<Playable>, target: Playable, type: MoveType) => {
    const orderHash = JSON.stringify(playlist.playables?.map(({ id }) => id))
    playlist.playables?.splice(
      0,
      playlist.playables.length,
      ...moveItemsInList(playlist.playables, songs, target, type)
    )

    if (orderHash !== JSON.stringify(playlist.playables?.map(({ id }) => id))) {
      await http.silently.post(`playlists/${playlist.id}/songs/move`, {
        songs: arrayify(songs).map(({ id }) => id),
        target: target.id,
        type
      })
    }
  },

  /**
   * Upload a cover for a playlist.
   *
   * @param {Playlist} playlist The playlist object
   * @param {string} cover The content data string of the cover
   */
  async uploadCover (playlist: Playlist, cover: string) {
    playlist.cover = (await http.put<{ cover_url: string }>(`playlists/${playlist.id}/cover`, { cover })).cover_url

    // sync to vault
    this.byId(playlist.id)!.cover = playlist.cover

    return playlist.cover
  },

  async removeCover (playlist: Playlist) {
    playlist.cover = null
    await http.delete(`playlists/${playlist.id}/cover`)
  }
}
