import { difference, union, orderBy } from 'lodash'

import stub from '@/stubs/playlist'
import { http } from '@/services'
import { alerts, pluralize } from '@/utils'
import { songStore } from '.'
import models from '@/config/smart-playlist/models'
import operators from '@/config/smart-playlist/operators'

export const playlistStore = {
  stub,

  state: {
    playlists: [] as Playlist[]
  },

  init (playlists: Playlist[]) {
    this.all = this.sort(playlists)
    this.all.forEach(playlist => this.setupPlaylist(playlist))
  },

  setupPlaylist (playlist: Playlist): void {
    playlist.songs = []

    if (playlist.is_smart) {
      this.setupSmartPlaylist(playlist)
    }
  },

  /**
   * Set up a smart playlist by properly construct its structure from serialized database values.
   */
  setupSmartPlaylist: (playlist: Playlist): void => {
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

  async fetchSongs (playlist: Playlist): Promise<Playlist> {
    const songIds = await http.get<string[]>(`playlist/${playlist.id}/songs`)
    playlist.songs = songStore.byIds(songIds)
    playlist.populated = true

    return playlist
  },

  byId (id: number): Playlist | undefined {
    return this.all.find(playlist => playlist.id === id)
  },

  /**
   * Populate the playlist content by "objectifying" all songs in the playlist.
   * (Initially, a playlist only contain the song IDs).
   */
  populateContent: (playlist: Playlist): void => {
    playlist.songs = songStore.byIds(<string[]><unknown>playlist.songs)
  },

  getSongs: (playlist: Playlist): Song[] => playlist.songs,

  /**
   * Add a playlist/playlists into the store.
   */
  add (playlists: Playlist | Playlist[]) {
    const playlistsToAdd = (<Playlist[]>[]).concat(playlists)
    playlistsToAdd.forEach(playlist => this.setupPlaylist(playlist))
    this.all = this.sort(union(this.all, playlistsToAdd))
  },

  /**
   * Remove a playlist/playlists from the store.
   */
  remove (playlists: Playlist | Playlist[]) {
    this.all = difference(this.all, (<Playlist[]>[]).concat(playlists))
  },

  async store (name: string, songs: Song[] = [], rules: SmartPlaylistRuleGroup[] = []): Promise<Playlist> {
    const songIds = songs.map(song => song.id)
    const serializedRules = this.serializeSmartPlaylistRulesForStorage(rules)

    const playlist = await http.post<Playlist>('playlist', { name, songs: songIds, rules: serializedRules })
    playlist.songs = songs
    this.populateContent(playlist)
    this.add(playlist)
    alerts.success(`Created playlist "${playlist.name}."`)

    return playlist
  },

  async delete (playlist: Playlist): Promise<void> {
    await http.delete(`playlist/${playlist.id}`)
    this.remove(playlist)
  },

  async addSongs (playlist: Playlist, songs: Song[]): Promise<Playlist> {
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

    await http.put(`playlist/${playlist.id}/sync`, { songs: playlist.songs.map(song => song.id) })
    alerts.success(`Added ${pluralize(songs.length, 'song')} into "${playlist.name}."`)

    return playlist
  },

  removeSongs: async (playlist: Playlist, songs: Song[]): Promise<Playlist> => {
    if (playlist.is_smart) {
      return playlist
    }

    playlist.songs = difference(playlist.songs, songs)
    await http.put(`playlist/${playlist.id}/sync`, { songs: playlist.songs.map(song => song.id) })
    alerts.success(`Removed ${pluralize(songs.length, 'song')} from "${playlist.name}."`)

    return playlist
  },

  async update (playlist: Playlist): Promise<Playlist> {
    const serializedRules = this.serializeSmartPlaylistRulesForStorage(playlist.rules)

    await http.put(`playlist/${playlist.id}`, { name: playlist.name, rules: serializedRules })
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
  serializeSmartPlaylistRulesForStorage: (ruleGroups: SmartPlaylistRuleGroup[]): object[] | null => {
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

  sort: (playlists: Playlist[]): Playlist[] => {
    return orderBy(playlists, ['is_smart', 'name'], ['desc', 'asc'])
  }
}
