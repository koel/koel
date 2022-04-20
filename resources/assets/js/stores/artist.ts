import { difference, take, orderBy } from 'lodash'

import { http } from '@/services'
import stub from '@/stubs/artist'
import { arrayify, use } from '@/utils'

const UNKNOWN_ARTIST_ID = 1
const VARIOUS_ARTISTS_ID = 2

export const artistStore = {
  stub,
  cache: {} as { [key: number]: Artist },

  state: {
    artists: [] as Artist[]
  },

  init (artists: Artist[]): void {
    this.all = artists

    // Traverse through artists array to get the cover and number of songs for each.
    this.all.forEach(artist => this.setupArtist(artist))
  },

  setupArtist (artist: Artist): void {
    artist.playCount = 0
    artist.info = null
    artist.albums = []
    artist.songs = []

    this.cache[artist.id] = artist
  },

  get all (): Artist[] {
    return this.state.artists
  },

  set all (value: Artist[]) {
    this.state.artists = value
  },

  byId (id: number): Artist | undefined {
    return this.cache[id]
  },

  byIds (ids: number[]): Artist[] {
    const artists = [] as Artist[]
    ids.forEach(id => use(this.byId(id), artist => artists.push(artist!)))
    return artists
  },

  add (artists: Artist | Artist[]) {
    arrayify(artists).forEach(artist => {
      this.setupArtist(artist)
      artist.playCount = artist.songs.reduce((count, song) => count + song.playCount, 0)
      this.all.push(artist)
    })
  },

  purify (): void {
    this.compact()
  },

  /**
   * Remove empty artists from the store.
   */
  compact (): void {
    const emptyArtists = this.all.filter(artist => artist.songs.length === 0)

    if (!emptyArtists.length) {
      return
    }

    this.all = difference(this.all, emptyArtists)
    emptyArtists.forEach(artist => delete this.cache[artist.id])
  },

  isVariousArtists: (artist: Artist) => artist.id === VARIOUS_ARTISTS_ID,

  isUnknownArtist: (artist: Artist) => artist.id === UNKNOWN_ARTIST_ID,

  getSongsByArtist: (artist: Artist) => artist.songs,

  getMostPlayed (n: number = 6): Artist[] {
    // Only non-unknown artists with actual play count are applicable.
    // Also, "Various Artists" doesn't count.
    const applicable = this.all.filter(artist => {
      return artist.playCount &&
        !this.isUnknownArtist(artist) &&
        !this.isVariousArtists(artist)
    })

    return take(orderBy(applicable, 'playCount', 'desc'), n)
  },

  /**
   * Upload an image for an artist.
   *
   * @param {Artist} artist The artist object
   * @param {string} image The content data string of the image
   */
  uploadImage: async (artist: Artist, image: string): Promise<string> => {
    const { imageUrl } = await http.put<{ imageUrl: string }>(`artist/${artist.id}/image`, { image })
    artist.image = imageUrl
    return artist.image
  }
}
