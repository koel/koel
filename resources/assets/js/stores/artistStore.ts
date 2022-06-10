import { reactive } from 'vue'
import { difference, orderBy, take, union } from 'lodash'
import { httpService } from '@/services'
import { arrayify } from '@/utils'

const UNKNOWN_ARTIST_ID = 1
const VARIOUS_ARTISTS_ID = 2

export const artistStore = {
  vault: new Map<number, Artist>(),

  state: reactive({
    artists: []
  }),

  byId (id: number) {
    return this.vault.get(id)
  },

  removeByIds (ids: number[]) {
    this.state.artists = difference(this.state.artists, ids.map(id => this.byId(id)))
    ids.forEach(id => this.vault.delete(id))
  },

  isVarious: (artist: Artist | number) => {
    if (typeof artist === 'number') return artist === VARIOUS_ARTISTS_ID
    return artist.id === VARIOUS_ARTISTS_ID
  },

  isUnknown: (artist: Artist | number) => (typeof artist === 'number')
    ? artist === UNKNOWN_ARTIST_ID
    : artist.id === UNKNOWN_ARTIST_ID,

  isStandard (artist: Artist | number) {
    return !this.isVarious(artist) && !this.isUnknown(artist)
  },

  uploadImage: async (artist: Artist, image: string) => {
    const { imageUrl } = await httpService.put<{ imageUrl: string }>(`artist/${artist.id}/image`, { image })
    artist.image = imageUrl
    return artist.image
  },

  syncWithVault (artists: Artist | Artist[]) {
    return arrayify(artists).map(artist => {
      let local = this.vault.get(artist.id)
      local = local ? Object.assign(local, artist) : reactive(artist)
      this.vault.set(artist.id, local)

      return local
    })
  },

  async resolve (id: number) {
    let artist = this.byId(id)

    if (!artist) {
      artist = await httpService.get<Artist>(`artists/${id}`)
      this.syncWithVault(artist)
    }

    return artist
  },

  async fetch (page: number) {
    const resource = await httpService.get<PaginatorResource>(`artists?page=${page}`)
    this.state.artists = union(this.state.artists, this.syncWithVault(resource.data))

    return resource.links.next ? ++resource.meta.current_page : null
  },

  getMostPlayed (count: number) {
    return take(
      orderBy(Array.from(this.vault.values()).filter(artist => this.isStandard(artist)), 'play_count', 'desc'),
      count
    )
  }
}
