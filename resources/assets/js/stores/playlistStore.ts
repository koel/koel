import { differenceBy, orderBy } from 'lodash'
import { reactive, UnwrapNestedRefs } from 'vue'
import { logger } from '@/utils'
import { cache, httpService } from '@/services'
import models from '@/config/smart-playlist/models'
import operators from '@/config/smart-playlist/operators'

export const playlistStore = {
  state: reactive({
    playlists: [] as Playlist[]
  }),

  init (playlists: Playlist[]) {
    this.sort(reactive(playlists)).forEach((playlist: Playlist) => {
      if (!playlist.is_smart) {
        this.state.playlists.push(playlist)
      } else {
        try {
          this.setupSmartPlaylist(playlist)
          this.state.playlists.push(playlist)
        } catch (error) {
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
        const model = models.find(model => model.name === rule.model as unknown as string)

        if (!model) {
          logger.error(`Invalid model ${rule.model} found in smart playlist ${playlist.name} (ID ${playlist.id})`)
          return
        }

        rule.model = model
      })
    })
  },

  byId (id: number) {
    return this.state.playlists.find(playlist => playlist.id === id)
  },

  byFolder (folder: PlaylistFolder) {
    return this.state.playlists.filter(playlist => playlist.folder_id === folder.id)
  },

  async store (name: string, songs: Song[] = [], rules: SmartPlaylistRuleGroup[] = []) {
    const playlist = reactive(await httpService.post<Playlist>('playlists', {
      name,
      songs: songs.map(song => song.id),
      rules: this.serializeSmartPlaylistRulesForStorage(rules)
    }))

    this.setupSmartPlaylist(playlist)

    this.state.playlists.push(playlist)
    this.state.playlists = this.sort(this.state.playlists)

    return playlist
  },

  async delete (playlist: Playlist) {
    await httpService.delete(`playlists/${playlist.id}`)
    this.state.playlists = differenceBy(this.state.playlists, [playlist], 'id')
  },

  async addSongs (playlist: Playlist, songs: Song[]) {
    if (playlist.is_smart) {
      return playlist
    }

    await httpService.post(`playlists/${playlist.id}/songs`, { songs: songs.map(song => song.id) })
    cache.remove(['playlist.songs', playlist.id])

    return playlist
  },

  removeSongs: async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart) {
      return playlist
    }

    await httpService.delete(`playlists/${playlist.id}/songs`, { songs: songs.map(song => song.id) })
    cache.remove(['playlist.songs', playlist.id])

    return playlist
  },

  async update (playlist: Playlist, data: Partial<Pick<Playlist, 'name' | 'rules'>>) {
    await httpService.put(`playlists/${playlist.id}`, {
      name: data.name,
      rules: this.serializeSmartPlaylistRulesForStorage(data.rules || [])
    })

    playlist.is_smart && cache.remove(['playlist.songs', playlist.id])
    Object.assign(playlist, data)

    return playlist
  },

  createEmptySmartPlaylistRule: (): SmartPlaylistRule => ({
    id: (new Date()).getTime(),
    model: models[0],
    operator: operators[0].operator,
    value: ['']
  }),

  createEmptySmartPlaylistRuleGroup (): SmartPlaylistRuleGroup {
    return {
      id: (new Date()).getTime(),
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

  sort: (playlists: Playlist[] | UnwrapNestedRefs<Playlist>[]) => {
    return orderBy(playlists, ['is_smart', 'name'], ['desc', 'asc'])
  }
}
