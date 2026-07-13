import type { UnwrapNestedRefs } from 'vue'
import { reactive } from 'vue'
import { http } from '@/services/http'
import { differenceBy, orderBy } from 'lodash-es'
import { playlistStore } from '@/stores/playlistStore'

type PlaylistFolderUpdateData = Partial<Pick<PlaylistFolder, 'name' | 'parent_id'>>

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

  byParent(parent: PlaylistFolder | null) {
    const folders = parent
      ? this.state.folders.filter(folder => folder.parent_id === parent.id)
      : this.state.folders.filter(folder => folder.parent_id === null)

    return this.sort(folders)
  },

  descendantsOf(folder: PlaylistFolder) {
    const descendants: PlaylistFolder[] = []
    const visitedFolderIds = new Set<PlaylistFolder['id']>([folder.id])
    const foldersToVisit = [...this.byParent(folder)].reverse()

    while (foldersToVisit.length) {
      const descendant = foldersToVisit.pop()!

      if (visitedFolderIds.has(descendant.id)) {
        continue
      }

      visitedFolderIds.add(descendant.id)
      descendants.push(descendant)
      foldersToVisit.push(...this.byParent(descendant).reverse())
    }

    return descendants
  },

  pathFor(folder: PlaylistFolder) {
    const path: PlaylistFolder['name'][] = []
    const visitedFolderIds = new Set<PlaylistFolder['id']>()
    let currentFolder: PlaylistFolder | undefined = folder

    while (currentFolder && !visitedFolderIds.has(currentFolder.id)) {
      visitedFolderIds.add(currentFolder.id)
      path.unshift(currentFolder.name)
      currentFolder = currentFolder.parent_id ? this.byId(currentFolder.parent_id) : undefined
    }

    return path.join(' / ')
  },

  playlistsInTree(folder: PlaylistFolder) {
    return [folder, ...this.descendantsOf(folder)].flatMap(currentFolder => playlistStore.byFolder(currentFolder))
  },

  async store(name: PlaylistFolder['name'], parent?: PlaylistFolder | null) {
    const data: { name: PlaylistFolder['name']; parent_id?: PlaylistFolder['id'] | null } = { name }

    if (parent !== undefined) {
      data.parent_id = parent ? parent.id : null
    }

    const folder = reactive(await http.post<PlaylistFolder>('playlist-folders', data))

    this.state.folders.push(folder)
    this.state.folders = orderBy(this.state.folders, 'name')

    return folder
  },

  async delete(folder: PlaylistFolder) {
    const childFolders = this.byParent(folder)
    const playlists = playlistStore.byFolder(folder)

    await http.delete(`playlist-folders/${folder.id}`)

    childFolders.forEach(childFolder => {
      childFolder.parent_id = null
    })
    playlists.forEach(playlist => {
      playlist.folder_id = null
    })
    this.state.folders = differenceBy(this.state.folders, [folder], 'id')
  },

  async rename(folder: PlaylistFolder, name: PlaylistFolder['name']) {
    await http.put(`playlist-folders/${folder.id}`, { name })
    this.byId(folder.id)!.name = name
  },

  async update(folder: PlaylistFolder, data: PlaylistFolderUpdateData) {
    await http.patch(`playlist-folders/${folder.id}`, data)
    Object.assign(this.byId(folder.id)!, data)
  },

  async moveFolderToFolder(folder: PlaylistFolder, parent: PlaylistFolder | null) {
    const parentId = parent?.id ?? null

    if (
      folder.parent_id === parentId ||
      parent?.id === folder.id ||
      (parent && this.descendantsOf(folder).some(descendant => descendant.id === parent.id))
    ) {
      return
    }

    await this.update(folder, { parent_id: parentId })
  },

  async movePlaylistToFolder(playlist: Playlist, folder: PlaylistFolder | null) {
    const targetFolderId = folder?.id ?? null

    if (playlist.folder_id === targetFolderId) {
      return
    }

    const sourceFolderId = playlist.folder_id

    // Update folder_id locally so the UI reflects the move immediately.
    playlist.folder_id = targetFolderId

    try {
      if (folder) {
        await http.post(`playlist-folders/${folder.id}/playlists`, { playlists: [playlist.id] })
      } else if (sourceFolderId) {
        await http.delete(`playlist-folders/${sourceFolderId}/playlists`, { playlists: [playlist.id] })
      }
    } catch (error) {
      // Roll the optimistic mutation back so the UI doesn't diverge from the server.
      playlist.folder_id = sourceFolderId
      throw error
    }
  },

  sort: (folders: PlaylistFolder[] | UnwrapNestedRefs<PlaylistFolder>[]) => orderBy(folders, 'name'),
}
