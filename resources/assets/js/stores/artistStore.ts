import { reactive } from 'vue'
import { differenceBy, unionBy } from 'lodash'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { arrayify } from '@/utils/helpers'
import { logger } from '@/utils/logger'

const UNKNOWN_ARTIST_NAME = 'Unknown Artist'
const VARIOUS_ARTISTS_NAME = 'Various Artists'

interface ArtistListPaginateParams extends Record<string, any> {
  sort: ArtistListSortField
  order: SortOrder
  page: number
}

export const artistStore = {
  vault: new Map<Artist['id'], Artist>(),

  state: reactive({
    artists: [] as Artist[],
  }),

  byId (id: Artist['id']) {
    return this.vault.get(id)
  },

  removeByIds (ids: Artist['id'][]) {
    this.state.artists = differenceBy(this.state.artists, ids.map(id => this.byId(id)), 'id')
    ids.forEach(id => this.vault.delete(id))
  },

  isVarious: (artist: Artist | Artist['name']) => typeof artist === 'string'
    ? artist === VARIOUS_ARTISTS_NAME
    : artist.name === VARIOUS_ARTISTS_NAME,

  isUnknown: (artist: Artist | Artist['name']) => typeof artist === 'string'
    ? artist === UNKNOWN_ARTIST_NAME
    : artist.name === UNKNOWN_ARTIST_NAME,

  isStandard (artist: Artist | Artist['name']) {
    return !this.isVarious(artist) && !this.isUnknown(artist)
  },

  async uploadImage (artist: Artist, image: string) {
    artist.image = (await http.put<{ image_url: string }>(`artists/${artist.id}/image`, { image })).image_url

    // sync to vault
    this.byId(artist.id)!.image = artist.image

    return artist.image
  },

  syncWithVault (artists: MaybeArray<Artist>) {
    return arrayify(artists).map(artist => {
      let local = this.vault.get(artist.id)
      local = local ? Object.assign(local, artist) : reactive(artist)
      this.vault.set(artist.id, local)

      return local
    })
  },

  async resolve (id: Artist['id']) {
    let artist = this.byId(id)

    if (!artist) {
      try {
        artist = this.syncWithVault(
          await cache.remember<Artist>(['artist', id], async () => await http.get<Artist>(`artists/${id}`)),
        )[0]
      } catch (error: unknown) {
        logger.error(error)
      }
    }

    return artist
  },

  async paginate (params: ArtistListPaginateParams) {
    const resource = await http.get<PaginatorResource<Artist>>(`artists?${new URLSearchParams(params).toString()}`)
    this.state.artists = unionBy(this.state.artists, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  reset () {
    this.vault.clear()
    this.state.artists = []
  },
}
