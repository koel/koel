import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('@/services/http', () => ({
  http: {
    patch: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

vi.mock('@/stores/playlistStore', () => ({
  playlistStore: {
    state: {
      playlists: [],
    },
    byFolder: vi.fn().mockReturnValue([]),
  },
}))

import { http } from '@/services/http'
import { playlistStore } from '@/stores/playlistStore'
import { playlistFolderStore } from './playlistFolderStore'

describe('playlistFolderStore', () => {
  const h = createHarness({
    beforeEach: () => {
      playlistFolderStore.state.folders = []
      playlistStore.state.playlists = []
      vi.mocked(http.patch).mockReset()
      vi.mocked(http.post).mockReset()
      vi.mocked(http.put).mockReset()
      vi.mocked(http.delete).mockReset()
      vi.mocked(playlistStore.byFolder).mockReset()
      vi.mocked(playlistStore.byFolder).mockImplementation(folder =>
        playlistStore.state.playlists.filter(playlist => playlist.folder_id === folder.id),
      )
    },
  })

  it('initializes with sorted folders', () => {
    const zebra = h.factory('playlist-folder').make({ name: 'Zebra' })
    const alpha = h.factory('playlist-folder').make({ name: 'Alpha' })

    playlistFolderStore.init([zebra, alpha])

    expect(playlistFolderStore.state.folders[0].name).toBe('Alpha')
    expect(playlistFolderStore.state.folders[1].name).toBe('Zebra')
  })

  it('finds folder by id', () => {
    const rock = h.factory('playlist-folder').make({ name: 'Rock' })
    const jazz = h.factory('playlist-folder').make({ name: 'Jazz' })

    playlistFolderStore.init([rock, jazz])

    expect(playlistFolderStore.byId(jazz.id)!.name).toBe('Jazz')
  })

  it('returns undefined for non-existent id', () => {
    playlistFolderStore.init([])
    expect(playlistFolderStore.byId('999')).toBeUndefined()
  })

  it('returns sorted direct children', () => {
    const root = h.factory('playlist-folder').make({ name: 'Zebra', parent_id: null })
    const alpha = h.factory('playlist-folder').make({ name: 'Alpha', parent_id: null })
    const child = h.factory('playlist-folder').make({ name: 'Child', parent_id: root.id })

    playlistFolderStore.init([root, child, alpha])

    expect(playlistFolderStore.byParent(null).map(folder => folder.name)).toEqual(['Alpha', 'Zebra'])
    expect(playlistFolderStore.byParent(root)).toEqual([child])
  })

  it('returns every descendant in sibling-name order', () => {
    const root = h.factory('playlist-folder').make({ parent_id: null })
    const zebra = h.factory('playlist-folder').make({ name: 'Zebra', parent_id: root.id })
    const alpha = h.factory('playlist-folder').make({ name: 'Alpha', parent_id: root.id })
    const grandchild = h.factory('playlist-folder').make({ name: 'Grandchild', parent_id: alpha.id })

    playlistFolderStore.init([zebra, grandchild, root, alpha])

    expect(playlistFolderStore.descendantsOf(root)).toEqual([alpha, grandchild, zebra])
  })

  it('builds full paths', () => {
    const root = h.factory('playlist-folder').make({ name: 'Music', parent_id: null })
    const child = h.factory('playlist-folder').make({ name: 'Live', parent_id: root.id })
    const grandchild = h.factory('playlist-folder').make({ name: '2026', parent_id: child.id })

    playlistFolderStore.init([root, child, grandchild])

    expect(playlistFolderStore.pathFor(grandchild)).toBe('Music / Live / 2026')
  })

  it('returns playlists from a folder and its descendants', () => {
    const root = h.factory('playlist-folder').make({ parent_id: null })
    const child = h.factory('playlist-folder').make({ parent_id: root.id })
    const sibling = h.factory('playlist-folder').make({ parent_id: null })
    const directPlaylist = h.factory('playlist').make({ folder_id: root.id })
    const childPlaylist = h.factory('playlist').make({ folder_id: child.id })
    const siblingPlaylist = h.factory('playlist').make({ folder_id: sibling.id })
    playlistFolderStore.init([root, child, sibling])
    playlistStore.state.playlists = [directPlaylist, childPlaylist, siblingPlaylist]

    expect(playlistFolderStore.playlistsInTree(root)).toEqual([directPlaylist, childPlaylist])
  })

  it('stores a new folder via API and adds sorted', async () => {
    const alpha = h.factory('playlist-folder').make({ name: 'Alpha' })
    playlistFolderStore.init([alpha])

    const beta = h.factory('playlist-folder').make({ name: 'Beta' })
    vi.mocked(http.post).mockResolvedValue(beta)

    const folder = await playlistFolderStore.store('Beta')

    expect(http.post).toHaveBeenCalledWith('playlist-folders', { name: 'Beta' })
    expect(folder.name).toBe('Beta')
    expect(playlistFolderStore.state.folders).toHaveLength(2)
    expect(playlistFolderStore.state.folders[0].name).toBe('Alpha')
    expect(playlistFolderStore.state.folders[1].name).toBe('Beta')
  })

  it('stores a new folder under a parent', async () => {
    const parent = h.factory('playlist-folder').make({ parent_id: null })
    const child = h.factory('playlist-folder').make({ name: 'Child', parent_id: parent.id })
    vi.mocked(http.post).mockResolvedValue(child)

    await playlistFolderStore.store('Child', parent)

    expect(http.post).toHaveBeenCalledWith('playlist-folders', {
      name: 'Child',
      parent_id: parent.id,
    })
  })

  it('deletes only one folder for legacy callers and promotes its direct contents to root', async () => {
    const folder = h.factory('playlist-folder').make({ name: 'Rock', parent_id: null })
    const child = h.factory('playlist-folder').make({ parent_id: folder.id })
    const grandchild = h.factory('playlist-folder').make({ parent_id: child.id })
    const directPlaylist = h.factory('playlist').make({ folder_id: folder.id })
    const childPlaylist = h.factory('playlist').make({ folder_id: child.id })
    playlistFolderStore.init([folder, child, grandchild])
    playlistStore.state.playlists = [directPlaylist, childPlaylist]
    vi.mocked(http.delete).mockResolvedValue({})

    await playlistFolderStore.delete(folder)

    expect(http.delete).toHaveBeenCalledWith(`playlist-folders/${folder.id}`)
    expect(playlistFolderStore.state.folders).toEqual(expect.arrayContaining([child, grandchild]))
    expect(playlistFolderStore.state.folders).toHaveLength(2)
    expect(child.parent_id).toBeNull()
    expect(grandchild.parent_id).toBe(child.id)
    expect(directPlaylist.folder_id).toBeNull()
    expect(childPlaylist.folder_id).toBe(child.id)
    expect(playlistStore.state.playlists).toEqual([directPlaylist, childPlaylist])
  })

  it('renames a folder', async () => {
    const folder = h.factory('playlist-folder').make({ name: 'Old' })
    playlistFolderStore.init([folder])
    vi.mocked(http.put).mockResolvedValue({})

    await playlistFolderStore.rename(folder, 'New')

    expect(http.put).toHaveBeenCalledWith(`playlist-folders/${folder.id}`, { name: 'New' })
    expect(playlistFolderStore.byId(folder.id)!.name).toBe('New')
  })

  it('updates a folder name and parent together', async () => {
    const folder = h.factory('playlist-folder').make({ name: 'Old', parent_id: null })
    const parent = h.factory('playlist-folder').make({ parent_id: null })
    vi.mocked(http.patch).mockResolvedValue({})
    playlistFolderStore.init([folder, parent])

    await playlistFolderStore.update(folder, { name: 'New', parent_id: parent.id })

    expect(http.patch).toHaveBeenCalledOnce()
    expect(http.patch).toHaveBeenCalledWith(`playlist-folders/${folder.id}`, {
      name: 'New',
      parent_id: parent.id,
    })
    expect(folder).toMatchObject({ name: 'New', parent_id: parent.id })
  })

  it('moves a folder under another folder and back to root', async () => {
    const folder = h.factory('playlist-folder').make({ parent_id: null })
    const parent = h.factory('playlist-folder').make({ parent_id: null })
    vi.mocked(http.patch).mockResolvedValue({})
    playlistFolderStore.init([folder, parent])

    await playlistFolderStore.moveFolderToFolder(folder, parent)

    expect(http.patch).toHaveBeenNthCalledWith(1, `playlist-folders/${folder.id}`, { parent_id: parent.id })
    expect(folder.parent_id).toBe(parent.id)

    await playlistFolderStore.moveFolderToFolder(folder, null)

    expect(http.patch).toHaveBeenNthCalledWith(2, `playlist-folders/${folder.id}`, { parent_id: null })
    expect(folder.parent_id).toBeNull()
  })

  it('does not move a folder to its current parent, itself, or a descendant', async () => {
    const root = h.factory('playlist-folder').make({ parent_id: null })
    const child = h.factory('playlist-folder').make({ parent_id: root.id })
    const grandchild = h.factory('playlist-folder').make({ parent_id: child.id })
    playlistFolderStore.init([root, child, grandchild])

    await playlistFolderStore.moveFolderToFolder(child, root)
    await playlistFolderStore.moveFolderToFolder(child, child)
    await playlistFolderStore.moveFolderToFolder(root, grandchild)

    expect(http.patch).not.toHaveBeenCalled()
    expect(root.parent_id).toBeNull()
    expect(child.parent_id).toBe(root.id)
  })

  it('leaves the folder in place when moving it fails', async () => {
    const folder = h.factory('playlist-folder').make({ parent_id: null })
    const parent = h.factory('playlist-folder').make({ parent_id: null })
    vi.mocked(http.patch).mockRejectedValue(new Error('boom'))
    playlistFolderStore.init([folder, parent])

    await expect(playlistFolderStore.moveFolderToFolder(folder, parent)).rejects.toThrow('boom')

    expect(folder.parent_id).toBeNull()
  })

  it('moves a playlist into a folder', async () => {
    const folder = h.factory('playlist-folder').make()
    const playlist = h.factory('playlist').make({ folder_id: null })
    vi.mocked(http.post).mockResolvedValue({})
    playlistFolderStore.init([folder])

    await playlistFolderStore.movePlaylistToFolder(playlist, folder)

    expect(playlist.folder_id).toBe(folder.id)
    expect(http.post).toHaveBeenCalledWith(`playlist-folders/${folder.id}/playlists`, { playlists: [playlist.id] })
  })

  it('moves a playlist out of a folder', async () => {
    const folder = h.factory('playlist-folder').make()
    const playlist = h.factory('playlist').make({ folder_id: folder.id })
    vi.mocked(http.delete).mockResolvedValue({})

    await playlistFolderStore.movePlaylistToFolder(playlist, null)

    expect(playlist.folder_id).toBeNull()
    expect(http.delete).toHaveBeenCalledWith(`playlist-folders/${folder.id}/playlists`, { playlists: [playlist.id] })
  })

  it('moves a playlist between folders via the target folder POST', async () => {
    const fromFolder = h.factory('playlist-folder').make()
    const toFolder = h.factory('playlist-folder').make()
    const playlist = h.factory('playlist').make({ folder_id: fromFolder.id })
    vi.mocked(http.post).mockResolvedValue({})
    playlistFolderStore.init([fromFolder, toFolder])

    await playlistFolderStore.movePlaylistToFolder(playlist, toFolder)

    expect(playlist.folder_id).toBe(toFolder.id)
    expect(http.post).toHaveBeenCalledWith(`playlist-folders/${toFolder.id}/playlists`, { playlists: [playlist.id] })
    expect(http.delete).not.toHaveBeenCalled()
  })

  it('no-ops when the playlist is already in the target folder', async () => {
    const folder = h.factory('playlist-folder').make()
    const playlist = h.factory('playlist').make({ folder_id: folder.id })
    playlistFolderStore.init([folder])

    await playlistFolderStore.movePlaylistToFolder(playlist, folder)

    expect(http.post).not.toHaveBeenCalled()
    expect(http.delete).not.toHaveBeenCalled()
  })

  it('no-ops when moving an already-orphan playlist to no folder', async () => {
    const playlist = h.factory('playlist').make({ folder_id: null })

    await playlistFolderStore.movePlaylistToFolder(playlist, null)

    expect(http.post).not.toHaveBeenCalled()
    expect(http.delete).not.toHaveBeenCalled()
  })

  it('rolls folder_id back when the move request fails', async () => {
    const folder = h.factory('playlist-folder').make()
    const playlist = h.factory('playlist').make({ folder_id: null })
    vi.mocked(http.post).mockRejectedValue(new Error('boom'))
    playlistFolderStore.init([folder])

    await expect(playlistFolderStore.movePlaylistToFolder(playlist, folder)).rejects.toThrow('boom')

    expect(playlist.folder_id).toBeNull()
  })

  it('sorts folders alphabetically', () => {
    const sorted = playlistFolderStore.sort([
      h.factory('playlist-folder').make({ name: 'Zebra' }),
      h.factory('playlist-folder').make({ name: 'Alpha' }),
      h.factory('playlist-folder').make({ name: 'Middle' }),
    ])

    expect(sorted.map(f => f.name)).toEqual(['Alpha', 'Middle', 'Zebra'])
  })
})
