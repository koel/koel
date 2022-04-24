import { difference, orderBy, take, union } from 'lodash'

import { artistStore } from '.'
import { httpService } from '@/services'
import { arrayify, use } from '@/utils'
import { reactive } from 'vue'

interface AlbumStoreState {
  albums: Album[]
}

const UNKNOWN_ALBUM_ID = 1

export const albumStore = {
  cache: {} as Record<number, Album>,

  state: reactive<AlbumStoreState>({
    albums: []
  }),

  init (albums: Album[]) {
    // Traverse through the artists array and add their albums into our master album list.
    this.all = albums
    this.all.forEach(album => this.setupAlbum(album))
  },

  setupAlbum (album: Album) {
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

  byId (id: number) {
    return this.cache[id]
  },

  byIds (ids: number[]) {
    const albums = [] as Album[]
    ids.forEach(id => use(this.byId(id), album => albums.push(album!)))
    return albums
  },

  add (albums: Album | Album[]) {
    arrayify(albums).forEach(album => {
      this.setupAlbum(album)
      album.playCount = album.songs.reduce((count, song) => count + song.playCount, 0)
      this.all.push(album)
    })
  },

  /**
   * Remove empty albums from the store.
   */
  compact () {
    const emptyAlbums = this.all.filter(album => album.songs.length === 0)
    if (!emptyAlbums.length) {
      return
    }

    this.all = difference(this.all, emptyAlbums)
    emptyAlbums.forEach(album => delete this.cache[album.id])
  },

  getMostPlayed (count = 6): Album[] {
    // Only non-unknown albums with actual play count are applicable.
    const applicable = this.all.filter(album => album.playCount && !this.isUnknownAlbum(album))
    return take(orderBy(applicable, 'playCount', 'desc'), count)
  },

  getRecentlyAdded (count = 6): Album[] {
    const applicable = this.all.filter(album => !this.isUnknownAlbum(album))
    return take(orderBy(applicable, 'created_at', 'desc'), count)
  },

  /**
   * Upload a cover for an album.
   *
   * @param {Album} album The album object
   * @param {string} cover The content data string of the cover
   */
  uploadCover: async (album: Album, cover: string) => {
    album.cover = (await httpService.put<{ coverUrl: string }>(`album/${album.id}/cover`, { cover })).coverUrl
    return album.cover
  },

  /**
   * Get the (blurry) thumbnail-sized version of an album's cover.
   */
  getThumbnail: async (album: Album) => {
    if (album.thumbnail === undefined) {
      album.thumbnail = (await httpService.get<{ thumbnailUrl: string }>(`album/${album.id}/thumbnail`)).thumbnailUrl
    }

    return album.thumbnail
  },

  isUnknownAlbum: (album: Album) => album.id === UNKNOWN_ALBUM_ID
}
