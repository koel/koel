import type { Reactive } from 'vue'
import { reactive } from 'vue'
import { differenceBy, unionBy } from 'lodash-es'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { flattenParams } from '@/utils/helpers'
import { logger } from '@/utils/logger'
import { useVault } from '@/composables/useVault'
import { playableStore as songStore } from '@/stores/playableStore'

const UNKNOWN_ARTIST_NAME = 'Unknown Artist'
const VARIOUS_ARTISTS_NAME = 'Various Artists'

export interface ArtistUpdateData {
  name: Artist['name']
  image?: Artist['image'] | null
}

interface ArtistListPaginateParams extends PaginateParams<ArtistListSortField> {
  favorites_only: boolean
}

export const artistStore = {
  ...useVault<Artist>(),

  state: reactive({
    artists: [] as Artist[],
  }),

  removeByIds(ids: Artist['id'][]) {
    this.state.artists = differenceBy(
      this.state.artists,
      ids.map(id => this.byId(id)),
      'id',
    )
    ids.forEach(id => this.vault.delete(id))
  },

  isVarious: (artist: Artist | Artist['name']) =>
    typeof artist === 'string' ? artist === VARIOUS_ARTISTS_NAME : artist.name === VARIOUS_ARTISTS_NAME,

  isUnknown: (artist: Artist | Artist['name']) =>
    typeof artist === 'string' ? artist === UNKNOWN_ARTIST_NAME : artist.name === UNKNOWN_ARTIST_NAME,

  isStandard(artist: Artist | Artist['name']) {
    return !this.isVarious(artist) && !this.isUnknown(artist)
  },

  async update(artist: Artist, data: ArtistUpdateData) {
    const updated = await http.put<Artist>(`artists/${artist.id}`, data)
    this.state.artists = unionBy(this.state.artists, this.syncWithVault(updated), 'id')
    songStore.syncArtistProperties(updated)
  },

  async resolve(id: Artist['id']) {
    let artist = this.byId(id)

    if (!artist) {
      try {
        artist = this.syncWithVault(
          await cache.remember(['artist', id], async () => await http.get<Artist>(`artists/${id}`)),
        )[0]
      } catch (error: unknown) {
        logger.error(error)
      }
    }

    return artist
  },

  async paginate(params: ArtistListPaginateParams) {
    const resource = await http.get<PaginatorResource<Artist>>(`artists?${new URLSearchParams(flattenParams(params))}`)
    this.state.artists = unionBy(this.state.artists, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  reset() {
    this.vault.clear()
    this.state.artists = []
  },

  async toggleFavorite(artist: Reactive<Artist>) {
    // Don't wait for the HTTP response to update the status, just toggle right away.
    // We'll update the liked status again after the HTTP request.
    artist.favorite = !artist.favorite

    const favorite = await http.post<Favorite | null>(`favorites/toggle`, {
      type: 'artist',
      id: artist.id,
    })

    artist.favorite = Boolean(favorite)
  },

  async fetchEvents(artist: Artist) {
    return await http.get<LiveEvent[]>(`artists/${artist.id}/events`)
  },
}
