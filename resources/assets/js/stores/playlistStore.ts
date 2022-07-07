import { difference, orderBy, union } from 'lodash'
import { reactive } from 'vue'

import { Cache, httpService } from '@/services'
import models from '@/config/smart-playlist/models'
import operators from '@/config/smart-playlist/operators'

export const playlistStore = {
  state: reactive({
    playlists: [] as Playlist[]
  }),

  init (playlists: Playlist[]) {
    this.state.playlists = this.sort(playlists)
    this.state.playlists.forEach(playlist => this.setupPlaylist(playlist))
  },

  setupPlaylist (playlist: Playlist) {
    playlist.songs = []
    playlist.is_smart && this.setupSmartPlaylist(playlist)
  },

  /**
   * Set up a smart playlist by properly construct its structure from serialized database values.
   */
  setupSmartPlaylist: (playlist: Playlist) => {
    playlist.rules.forEach(group => {
      group.rules.forEach(rule => {
        const model = models.find(model => model.name === rule.model as unknown as string)

        if (!model) {
          console.error(`Invalid model ${rule.model} found in smart playlist ${playlist.name} (ID ${playlist.id})`)
          return
        }

        rule.model = model
      })
    })
  },

  byId (id: number) {
    return this.state.playlists.find(playlist => playlist.id === id)
  },

  async store (name: string, songs: Song[] = [], rules: SmartPlaylistRuleGroup[] = []) {
    const songIds = songs.map(song => song.id)
    const serializedRules = this.serializeSmartPlaylistRulesForStorage(rules)

    const playlist = await httpService.post<Playlist>('playlist', { name, songs: songIds, rules: serializedRules })
    this.state.playlists.push(playlist)
    this.state.playlists = this.sort(this.state.playlists)

    return playlist
  },

  async delete (playlist: Playlist) {
    await httpService.delete(`playlists/${playlist.id}`)
    this.state.playlists = difference(this.state.playlists, [playlist])
  },

  async addSongs (playlist: Playlist, songs: Song[]) {
    if (playlist.is_smart) {
      return playlist
    }

    const count = playlist.songs.length
    playlist.songs = union(playlist.songs, songs)

    if (count === playlist.songs.length) {
      return playlist
    }

    await httpService.post(`playlists/${playlist.id}/songs`, { songs: songs.map(song => song.id) })
    Cache.invalidate(['playlist.songs', playlist.id])

    return playlist
  },

  removeSongs: async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart) {
      return playlist
    }

    playlist.songs = difference(playlist.songs, songs)
    await httpService.delete(`playlists/${playlist.id}/songs`, { songs: songs.map(song => song.id) })
    Cache.invalidate(['playlist.songs', playlist.id])

    return playlist
  },

  async update (playlist: Playlist) {
    const serializedRules = this.serializeSmartPlaylistRulesForStorage(playlist.rules)
    await httpService.put(`playlists/${playlist.id}`, { name: playlist.name, rules: serializedRules })

    playlist.is_smart && Cache.invalidate(['playlist.songs', playlist.id])

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

  sort: (playlists: Playlist[]) => {
    return orderBy(playlists, ['is_smart', 'name'], ['desc', 'asc'])
  }
}
