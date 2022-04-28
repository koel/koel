import { difference, orderBy, take } from 'lodash'

import { httpService } from '@/services'
import stub from '@/stubs/artist'
import { arrayify, use } from '@/utils'
import { reactive } from 'vue'

interface ArtistStoreState {
  artists: Artist[]
}

const UNKNOWN_ARTIST_ID = 1
const VARIOUS_ARTISTS_ID = 2

export const artistStore = {
  stub,
  cache: {} as Record<number, Artist>,

  state: reactive<ArtistStoreState>({
    artists: []
  }),

  init (artists: Artist[]) {
    this.all = artists

    // Traverse through artists array to get the cover and number of songs for each.
    this.all.forEach(artist => this.setupArtist(artist))
  },

  setupArtist (artist: Artist) {
    artist.info = artist.info || null
    artist.albums = artist.albums || []
    artist.songs = artist.songs || []
    artist.playCount = artist.songs.reduce((count, song) => count + song.playCount, 0)

    this.cache[artist.id] = artist

    return artist
  },

  get all () {
    return this.state.artists
  },

  set all (value: Artist[]) {
    this.state.artists = value
  },

  byId (id: number): Artist | undefined {
    return this.cache[id]
  },

  byIds (ids: number[]) {
    const artists = [] as Artist[]
    ids.forEach(id => use(this.byId(id), artist => artists.push(artist!)))
    return artists
  },

  add (artists: Artist | Artist[]) {
    arrayify(artists).forEach(artist => this.all.push(this.setupArtist(artist)))
  },

  prepend (artists: Artist | Artist[]) {
    arrayify(artists).forEach(artist => this.all.unshift(this.setupArtist(artist)))
  },

  /**
   * Remove empty artists from the store.
   */
  compact () {
    const emptyArtists = this.all.filter(artist => artist.songs.length === 0)

    if (!emptyArtists.length) {
      return
    }

    this.all = difference(this.all, emptyArtists)
    emptyArtists.forEach(artist => delete this.cache[artist.id])
  },

  isVariousArtists: (artist: Artist) => artist.id === VARIOUS_ARTISTS_ID,

  isUnknownArtist: (artist: Artist) => artist.id === UNKNOWN_ARTIST_ID,

  getMostPlayed (count = 6): Artist[] {
    // Only non-unknown artists with actual play count are applicable.
    // Also, "Various Artists" doesn't count.
    const applicable = this.all.filter(artist => {
      return artist.playCount &&
        !this.isUnknownArtist(artist) &&
        !this.isVariousArtists(artist)
    })

    return take(orderBy(applicable, 'playCount', 'desc'), count)
  },

  /**
   * Upload an image for an artist.
   *
   * @param {Artist} artist The artist object
   * @param {string} image The content data string of the image
   */
  uploadImage: async (artist: Artist, image: string) => {
    const { imageUrl } = await httpService.put<{ imageUrl: string }>(`artist/${artist.id}/image`, { image })
    artist.image = imageUrl
    return artist.image
  }
}
