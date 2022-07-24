import { reactive, UnwrapNestedRefs } from 'vue'
import { differenceBy, orderBy, take, unionBy } from 'lodash'
import { Cache, httpService } from '@/services'
import { arrayify, logger } from '@/utils'

const UNKNOWN_ARTIST_ID = 1
const VARIOUS_ARTISTS_ID = 2

export const artistStore = {
  vault: new Map<number, UnwrapNestedRefs<Artist>>(),

  state: reactive({
    artists: []
  }),

  byId (id: number) {
    return this.vault.get(id)
  },

  removeByIds (ids: number[]) {
    this.state.artists = differenceBy(this.state.artists, ids.map(id => this.byId(id)), 'id')
    ids.forEach(id => this.vault.delete(id))
  },

  isVarious: (artist: Artist | number) => (typeof artist === 'number')
    ? artist === VARIOUS_ARTISTS_ID
    : artist.id === VARIOUS_ARTISTS_ID,

  isUnknown: (artist: Artist | number) => (typeof artist === 'number')
    ? artist === UNKNOWN_ARTIST_ID
    : artist.id === UNKNOWN_ARTIST_ID,

  isStandard (artist: Artist | number) {
    return !this.isVarious(artist) && !this.isUnknown(artist)
  },

  async uploadImage (artist: Artist, image: string) {
    artist.image = (await httpService.put<{ imageUrl: string }>(`artist/${artist.id}/image`, { image })).imageUrl

    // sync to vault
    this.byId(artist.id).image = artist.image

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
      try {
        artist = this.syncWithVault(
          await Cache.resolve<Artist>(['artist', id], async () => await httpService.get<Artist>(`artists/${id}`))
        )[0]
      } catch (e) {
        logger.error(e)
      }
    }

    return artist
  },

  async paginate (page: number) {
    const resource = await httpService.get<PaginatorResource>(`artists?page=${page}`)
    this.state.artists = unionBy(this.state.artists, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  getMostPlayed (count: number) {
    return take(
      orderBy(Array.from(this.vault.values()).filter(artist => this.isStandard(artist)), 'play_count', 'desc'),
      count
    )
  }
}
