import { reactive, UnwrapNestedRefs } from 'vue'
import { differenceBy, merge, unionBy } from 'lodash'
import { cache, http } from '@/services'
import { arrayify, logger } from '@/utils'
import { songStore } from '@/stores'

const UNKNOWN_ALBUM_ID = 1

export const albumStore = {
  vault: new Map<number, UnwrapNestedRefs<Album>>(),

  state: reactive({
    albums: [] as Album[]
  }),

  byId (id: number) {
    return this.vault.get(id)
  },

  removeByIds (ids: number[]) {
    this.state.albums = differenceBy(this.state.albums, ids.map(id => this.byId(id)), 'id')
    ids.forEach(id => {
      this.vault.delete(id)
      cache.remove(['album', id])
    })
  },

  isUnknown: (album: Album | number) => {
    if (typeof album === 'number') return album === UNKNOWN_ALBUM_ID
    return album.id === UNKNOWN_ALBUM_ID
  },

  syncWithVault (albums: Album | Album[]) {
    return arrayify(albums).map(album => {
      let local = this.vault.get(album.id)
      local = reactive(local ? merge(local, album) : album)
      this.vault.set(album.id, local)

      return local
    })
  },

  /**
   * Upload a cover for an album.
   *
   * @param {Album} album The album object
   * @param {string} cover The content data string of the cover
   */
  async uploadCover (album: Album, cover: string) {
    album.cover = (await http.put<{ coverUrl: string }>(`album/${album.id}/cover`, { cover })).coverUrl
    songStore.byAlbum(album).forEach(song => song.album_cover = album.cover)

    // sync to vault
    this.byId(album.id)!.cover = album.cover

    return album.cover
  },

  /**
   * Fetch the (blurry) thumbnail-sized version of an album's cover.
   */
  fetchThumbnail: async (id: number) => {
    return (await http.get<{ thumbnailUrl: string }>(`album/${id}/thumbnail`)).thumbnailUrl
  },

  async resolve (id: number) {
    let album = this.byId(id)

    if (!album) {
      try {
        album = this.syncWithVault(
          await cache.remember<Album>(['album', id], async () => await http.get<Album>(`albums/${id}`))
        )[0]
      } catch (e) {
        logger.error(e)
      }
    }

    return album
  },

  async paginate (page: number) {
    const resource = await http.get<PaginatorResource>(`albums?page=${page}`)
    this.state.albums = unionBy(this.state.albums, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  }
}
