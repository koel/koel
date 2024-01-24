import { reactive, UnwrapNestedRefs } from 'vue'
import { http } from '@/services'
import { differenceBy, orderBy } from 'lodash'
import { playlistStore } from '@/stores/playlistStore'

export const playlistFolderStore = {
  state: reactive<{ folders: PlaylistFolder [] }>({
    folders: []
  }),

  init (folders: PlaylistFolder[]) {
    this.state.folders = this.sort(reactive(folders))
  },

  byId (id: string) {
    return this.state.folders.find(folder => folder.id === id)
  },

  async store (name: string) {
    const folder = reactive(await http.post<PlaylistFolder>('playlist-folders', { name }))

    this.state.folders.push(folder)
    this.state.folders = orderBy(this.state.folders, 'name')

    return folder
  },

  async delete (folder: PlaylistFolder) {
    await http.delete(`playlist-folders/${folder.id}`)
    this.state.folders = differenceBy(this.state.folders, [folder], 'id')
    playlistStore.byFolder(folder).forEach(playlist => (playlist.folder_id = null))
  },

  async rename (folder: PlaylistFolder, name: string) {
    await http.put(`playlist-folders/${folder.id}`, { name })
    this.byId(folder.id)!.name = name
  },

  async addPlaylistToFolder (folder: PlaylistFolder, playlist: Playlist) {
    // Update the folder ID right away, so that the UI can be refreshed immediately.
    // The actual HTTP request will be done in the background.
    playlist.folder_id = folder.id
    await http.post(`playlist-folders/${folder.id}/playlists`, { playlists: [playlist.id] })
  },

  async removePlaylistFromFolder (folder: PlaylistFolder, playlist: Playlist) {
    // Update the folder ID right away, so that the UI can be updated immediately.
    // The actual update will be done in the background.
    playlist.folder_id = null
    await http.delete(`playlist-folders/${folder.id}/playlists`, { playlists: [playlist.id] })
  },

  sort: (folders: PlaylistFolder[] | UnwrapNestedRefs<PlaylistFolder>[]) => orderBy(folders, 'name')
}
