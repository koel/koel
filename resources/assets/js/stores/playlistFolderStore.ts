import type { UnwrapNestedRefs } from 'vue'
import { reactive } from 'vue'
import { http } from '@/services/http'
import { differenceBy, orderBy } from 'lodash-es'
import { playlistStore } from '@/stores/playlistStore'

export const playlistFolderStore = {
  state: reactive<{ folders: PlaylistFolder[] }>({
    folders: [],
  }),

  init(folders: PlaylistFolder[]) {
    this.state.folders = this.sort(reactive(folders))
  },

  byId(id: PlaylistFolder['id']) {
    return this.state.folders.find(folder => folder.id === id)
  },

  async store(name: PlaylistFolder['name']) {
    const folder = reactive(await http.post<PlaylistFolder>('playlist-folders', { name }))

    this.state.folders.push(folder)
    this.state.folders = orderBy(this.state.folders, 'name')

    return folder
  },

  async delete(folder: PlaylistFolder) {
    await http.delete(`playlist-folders/${folder.id}`)
    this.state.folders = differenceBy(this.state.folders, [folder], 'id')
    playlistStore.byFolder(folder).forEach(playlist => (playlist.folder_id = null))
  },

  async rename(folder: PlaylistFolder, name: PlaylistFolder['name']) {
    await http.put(`playlist-folders/${folder.id}`, { name })
    this.byId(folder.id)!.name = name
  },

  async movePlaylistToFolder(playlist: Playlist, folder: PlaylistFolder | null) {
    const targetFolderId = folder?.id ?? null

    if (playlist.folder_id === targetFolderId) {
      return
    }

    const sourceFolderId = playlist.folder_id

    // Update folder_id locally so the UI reflects the move immediately.
    playlist.folder_id = targetFolderId

    if (folder) {
      await http.post(`playlist-folders/${folder.id}/playlists`, { playlists: [playlist.id] })
    } else if (sourceFolderId) {
      await http.delete(`playlist-folders/${sourceFolderId}/playlists`, { playlists: [playlist.id] })
    }
  },

  sort: (folders: PlaylistFolder[] | UnwrapNestedRefs<PlaylistFolder>[]) => orderBy(folders, 'name'),
}
