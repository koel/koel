/* eslint camelcase: ["error", {properties: "never"}] */
import { union, difference, take, orderBy } from 'lodash'

import stub from '@/stubs/album'
import { artistStore } from '.'
import { http } from '@/services'
import { use } from '@/utils'

const UNKNOWN_ALBUM_ID = 1

export const albumStore = {
  stub,
  cache: {} as { [key: string]: Album },

  state: {
    albums: [stub]
  },

  init (albums: Album[]) {
    // Traverse through the artists array and add their albums into our master album list.
    this.all = albums
    this.all.forEach(album => this.setupAlbum(album))
  },

  setupAlbum (album: Album): void {
    const artist = artistStore.byId(album.artist_id)!
    artist.albums = union(artist.albums, [album])

    album.artist = artist
    album.info = null
    album.songs = []
    album.playCount = 0

    this.cache[album.id] = album
  },

  get all () {
    return this.state.albums
  },

  set all (value) {
    this.state.albums = value
  },

  byId (id: number): Album | undefined {
    return this.cache[id]
  },

  byIds (ids: number[]): Album[] {
    const albums = [] as Album[]
    ids.forEach(id => use(this.byId(id), album => albums.push(album!)))
    return albums
  },

  add (albums: Album | Album[]): void {
    (<Album[]>[]).concat(albums).forEach(album => {
      this.setupAlbum(album)
      album.playCount = album.songs.reduce((count, song) => count + song.playCount, 0)
      this.all.push(album)
    })
  },

  purify (): void {
    this.compact()
  },

  /**
   * Remove empty albums from the store.
   */
  compact (): void {
    const emptyAlbums = this.all.filter(album => album.songs.length === 0)
    if (!emptyAlbums.length) {
      return
    }

    this.all = difference(this.all, emptyAlbums)
    emptyAlbums.forEach(album => delete this.cache[album.id])
  },

  getMostPlayed (n: number = 6): Album[] {
    // Only non-unknown albums with actual play count are applicable.
    const applicable = this.all.filter(album => album.playCount && album.id !== 1)
    return take(orderBy(applicable, 'playCount', 'desc'), n)
  },

  getRecentlyAdded (n: number = 6): Album[] {
    const applicable = this.all.filter(album => album.id !== 1)
    return take(orderBy(applicable, 'created_at', 'desc'), n)
  },

  /**
   * Upload a cover for an album.
   *
   * @param {Album} album The album object
   * @param {string} cover The content data string of the cover
   */
  uploadCover: async (album: Album, cover: string): Promise<string> => {
    const { coverUrl } = await http.put<{ coverUrl: string }>(`album/${album.id}/cover`, { cover })
    album.cover = coverUrl
    return album.cover
  },

  /**
   * Get the (blurry) thumbnail-sized version of an album's cover.
   */
  getThumbnail: async (album: Album): Promise<string | null> => {
    if (album.thumbnail === undefined) {
      const { thumbnailUrl } = await http.get<{ thumbnailUrl: string }>(`album/${album.id}/thumbnail`)

      album.thumbnail = thumbnailUrl
    }

    return album.thumbnail
  },

  isUnknownAlbum: (album: Album) => album.id === UNKNOWN_ALBUM_ID
}
