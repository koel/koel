import type { Reactive } from 'vue'
import { reactive } from 'vue'
import { differenceBy, merge, unionBy } from 'lodash'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { arrayify, use } from '@/utils/helpers'
import { logger } from '@/utils/logger'
import { playableStore as songStore } from '@/stores/playableStore'

const UNKNOWN_ALBUM_NAME = 'Unknown Album'

export type AlbumUpdateData = Pick<Album, 'name' | 'year' | 'cover'>

interface AlbumListPaginateParams extends Record<string, any> {
  favorites_only: boolean
  sort: AlbumListSortField
  order: SortOrder
  page: number
}

export const albumStore = {
  vault: new Map<Album['id'], Album>(),

  state: reactive({
    albums: [] as Album[],
  }),

  byId (id: Album['id']) {
    return this.vault.get(id)
  },

  removeByIds (ids: Album['id'][]) {
    this.state.albums = differenceBy(this.state.albums, ids.map(id => this.byId(id)), 'id')
    ids.forEach(id => {
      this.vault.delete(id)
      cache.remove(['album', id])
    })
  },

  isUnknown: (album: Album | Album['name']) => {
    if (typeof album === 'string') {
      return album === UNKNOWN_ALBUM_NAME
    }

    return album.name === UNKNOWN_ALBUM_NAME
  },

  syncWithVault (albums: MaybeArray<Album>) {
    return arrayify(albums).map(album => {
      let local = this.vault.get(album.id)
      local = reactive(local ? merge(local, album) : album)
      this.vault.set(album.id, local)

      return local
    })
  },

  async update (album: Album, data: AlbumUpdateData) {
    const updated = await http.put<Album>(`albums/${album.id}`, data)
    this.state.albums = unionBy(this.state.albums, this.syncWithVault(updated), 'id')

    songStore.syncAlbumProperties(album)
  },

  /**
   * Fetch the (blurry) thumbnail-sized version of an album's cover.
   */
  fetchThumbnail: async (id: Album['id']) => {
    return (await http.get<{ thumbnailUrl: string }>(`albums/${id}/thumbnail`)).thumbnailUrl
  },

  async resolve (id: Album['id']) {
    let album = this.byId(id)

    if (!album) {
      try {
        album = this.syncWithVault(
          await cache.remember(['album', id], async () => await http.get<Album>(`albums/${id}`)),
        )[0]
      } catch (error: unknown) {
        logger.error(error)
      }
    }

    return album
  },

  async paginate (params: AlbumListPaginateParams) {
    const resource = await http.get<PaginatorResource<Album>>(`albums?${new URLSearchParams(params).toString()}`)
    this.state.albums = unionBy(this.state.albums, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  async fetchForArtist (artist: Artist | Artist['id']) {
    const id = typeof artist === 'string' ? artist : artist.id

    return this.syncWithVault(
      await cache.remember(['artist-albums', id], async () => await http.get<Album[]>(`artists/${id}/albums`)),
    )
  },

  async toggleFavorite (album: Reactive<Album>) {
    // Don't wait for the HTTP response to update the status, just toggle right away.
    // We'll update the liked status again after the HTTP request.
    album.favorite = !album.favorite

    const favorite = await http.post<Favorite | null>(`favorites/toggle`, {
      type: 'album',
      id: album.id,
    })

    album.favorite = Boolean(favorite)
  },

  async removeCover (album: Album) {
    await http.delete(`albums/${album.id}/cover`)

    use(this.byId(album.id), album => {
      album.cover = ''
      songStore.syncAlbumProperties(album)
    })
  },

  reset () {
    this.vault.clear()
    this.state.albums = []
  },
}
