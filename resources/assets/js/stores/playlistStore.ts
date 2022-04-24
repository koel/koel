import { difference, union, orderBy } from 'lodash'

import stub from '@/stubs/playlist'
import { httpService } from '@/services'
import { alerts, pluralize, arrayify } from '@/utils'
import { songStore } from '.'
import models from '@/config/smart-playlist/models'
import operators from '@/config/smart-playlist/operators'
import { reactive } from 'vue'

export const playlistStore = {
  stub,

  state: reactive({
    playlists: [] as Playlist[]
  }),

  init (playlists: Playlist[]) {
    this.all = this.sort(playlists)
    this.all.forEach(playlist => this.setupPlaylist(playlist))
  },

  setupPlaylist (playlist: Playlist) {
    playlist.songs = []

    if (playlist.is_smart) {
      this.setupSmartPlaylist(playlist)
    }
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

  get all (): Playlist[] {
    return this.state.playlists
  },

  set all (value: Playlist[]) {
    this.state.playlists = value
  },

  async fetchSongs (playlist: Playlist) {
    const songIds = await httpService.get<string[]>(`playlist/${playlist.id}/songs`)
    playlist.songs = songStore.byIds(songIds)
    playlist.populated = true

    return playlist
  },

  byId (id: number) {
    return this.all.find(playlist => playlist.id === id)
  },

  /**
   * Populate the playlist content by "objectifying" all songs in the playlist.
   * (Initially, a playlist only contain the song IDs).
   */
  populateContent: (playlist: Playlist) => {
    playlist.songs = songStore.byIds(<string[]><unknown>playlist.songs)
  },

  getSongs: (playlist: Playlist): Song[] => playlist.songs,

  /**
   * Add a playlist/playlists into the store.
   */
  add (playlists: Playlist | Playlist[]) {
    const playlistsToAdd = arrayify(playlists)
    playlistsToAdd.forEach(playlist => this.setupPlaylist(playlist))
    this.all = this.sort(union(this.all, playlistsToAdd))
  },

  /**
   * Remove a playlist/playlists from the store.
   */
  remove (playlists: Playlist | Playlist[]) {
    this.all = difference(this.all, arrayify(playlists))
  },

  async store (name: string, songs: Song[] = [], rules: SmartPlaylistRuleGroup[] = []) {
    const songIds = songs.map(song => song.id)
    const serializedRules = this.serializeSmartPlaylistRulesForStorage(rules)

    const playlist = await httpService.post<Playlist>('playlist', { name, songs: songIds, rules: serializedRules })
    playlist.songs = songs
    this.populateContent(playlist)
    this.add(playlist)
    alerts.success(`Created playlist "${playlist.name}."`)

    return playlist
  },

  async delete (playlist: Playlist) {
    await httpService.delete(`playlist/${playlist.id}`)
    this.remove(playlist)
  },

  async addSongs (playlist: Playlist, songs: Song[]) {
    if (playlist.is_smart) {
      return playlist
    }

    if (!playlist.populated) {
      await this.fetchSongs(playlist)
    }

    const count = playlist.songs.length
    playlist.songs = union(playlist.songs, songs)

    if (count === playlist.songs.length) {
      return playlist
    }

    await httpService.put(`playlist/${playlist.id}/sync`, { songs: playlist.songs.map(song => song.id) })
    alerts.success(`Added ${pluralize(songs.length, 'song')} into "${playlist.name}."`)

    return playlist
  },

  removeSongs: async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart) {
      return playlist
    }

    playlist.songs = difference(playlist.songs, songs)
    await httpService.put(`playlist/${playlist.id}/sync`, { songs: playlist.songs.map(song => song.id) })
    alerts.success(`Removed ${pluralize(songs.length, 'song')} from "${playlist.name}."`)

    return playlist
  },

  async update (playlist: Playlist) {
    const serializedRules = this.serializeSmartPlaylistRulesForStorage(playlist.rules)

    await httpService.put(`playlist/${playlist.id}`, { name: playlist.name, rules: serializedRules })
    alerts.success(`Updated playlist "${playlist.name}."`)

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
  serializeSmartPlaylistRulesForStorage: (ruleGroups: SmartPlaylistRuleGroup[]): any[] | null => {
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
