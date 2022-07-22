import { reactive } from 'vue'
import { differenceBy, merge, orderBy, take, unionBy } from 'lodash'
import { Cache, httpService } from '@/services'
import { arrayify } from '@/utils'
import { songStore } from '@/stores'

const UNKNOWN_ALBUM_ID = 1

export const albumStore = {
  vault: new Map<number, Album>(),

  state: reactive({
    albums: []
  }),

  byId (id: number) {
    return this.vault.get(id)
  },

  removeByIds (ids: number[]) {
    this.state.albums = differenceBy(this.state.albums, ids.map(id => this.byId(id)), 'id')
    ids.forEach(id => this.vault.delete(id))
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
    album.cover = (await httpService.put<{ coverUrl: string }>(`album/${album.id}/cover`, { cover })).coverUrl
    songStore.byAlbum(album).forEach(song => song.album_cover = album.cover)

    // sync to vault
    this.byId(album.id).cover = album.cover

    return album.cover
  },

  /**
   * Fetch the (blurry) thumbnail-sized version of an album's cover.
   */
  fetchThumbnail: async (id: number) => {
    return (await httpService.get<{ thumbnailUrl: string }>(`album/${id}/thumbnail`)).thumbnailUrl
  },

  async resolve (id: number) {
    let album = this.byId(id)

    if (!album) {
      album = await Cache.resolve<Album>(['album', id], async () => await httpService.get<Album>(`albums/${id}`))
      this.syncWithVault(album)
    }

    return album
  },

  async paginate (page: number) {
    const resource = await httpService.get<PaginatorResource>(`albums?page=${page}`)
    this.state.albums = unionBy(this.state.albums, this.syncWithVault(resource.data), 'id')

    return resource.links.next ? ++resource.meta.current_page : null
  },

  getMostPlayed (count: number) {
    return take(
      orderBy(Array.from(this.vault.values()).filter(album => !this.isUnknown(album)), 'play_count', 'desc'),
      count
    )
  },

  getRecentlyAdded (count: number) {
    return take(
      orderBy(Array.from(this.vault.values()).filter(album => !this.isUnknown(album)), 'created_at', 'desc'),
      count
    )
  }
}
