import { reactive, UnwrapNestedRefs } from 'vue'
import { httpService } from '@/services'
import { differenceBy, orderBy } from 'lodash'
import { playlistStore } from '@/stores/playlistStore'

export const playlistFolderStore = {
  state: reactive({
    folders: [] as PlaylistFolder[]
  }),

  init (folders: PlaylistFolder[]) {
    this.state.folders = this.sort(reactive(folders))
  },

  async store (name: string) {
    const folder = reactive(await httpService.post<PlaylistFolder>('playlist-folders', { name }))

    this.state.folders.push(folder)
    this.state.folders = orderBy(this.state.folders, 'name')

    return folder
  },

  async delete (folder: PlaylistFolder) {
    await httpService.delete(`playlist-folders/${folder.id}`)
    this.state.folders = differenceBy(this.state.folders, [folder], 'id')
    playlistStore.byFolder(folder).forEach(playlist => (playlist.folder_id = null))
  },

  async rename (folder: PlaylistFolder, name: string) {
    await httpService.put(`playlist-folders/${folder.id}`, { name })
  },

  async addPlaylistToFolder (folder: PlaylistFolder, playlist: Playlist) {
    // Update the folder ID right away, so that the UI can be updated immediately.
    // The actual update will be done in the background.
    playlist.folder_id = folder.id
    await httpService.post(`playlist-folders/${folder.id}/playlists`, { playlists: [playlist.id] })
  },

  async removePlaylistFromFolder (folder: PlaylistFolder, playlist: Playlist) {
    // Update the folder ID right away, so that the UI can be updated immediately.
    // The actual update will be done in the background.
    playlist.folder_id = null
    await httpService.delete(`playlist-folders/${folder.id}/playlists`, { playlists: [playlist.id] })
  },

  sort: (folders: PlaylistFolder[] | UnwrapNestedRefs<PlaylistFolder>[]) => orderBy(folders, 'name')
}
