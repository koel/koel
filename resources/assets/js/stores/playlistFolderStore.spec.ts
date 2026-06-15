import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('@/services/http', () => ({
  http: {
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}))

vi.mock('@/stores/playlistStore', () => ({
  playlistStore: {
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
      vi.mocked(http.post).mockReset()
      vi.mocked(http.put).mockReset()
      vi.mocked(http.delete).mockReset()
      vi.mocked(playlistStore.byFolder).mockReturnValue([])
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

  it('deletes a folder and unlinks playlists', async () => {
    const folder = h.factory('playlist-folder').make({ name: 'Rock' })
    playlistFolderStore.init([folder])

    const playlist = h.factory('playlist').make({ folder_id: folder.id })
    vi.mocked(playlistStore.byFolder).mockReturnValue([playlist])
    vi.mocked(http.delete).mockResolvedValue({})

    await playlistFolderStore.delete(folder)

    expect(http.delete).toHaveBeenCalledWith(`playlist-folders/${folder.id}`)
    expect(playlistFolderStore.state.folders).toHaveLength(0)
    expect(playlist.folder_id).toBeNull()
  })

  it('renames a folder', async () => {
    const folder = h.factory('playlist-folder').make({ name: 'Old' })
    playlistFolderStore.init([folder])
    vi.mocked(http.put).mockResolvedValue({})

    await playlistFolderStore.rename(folder, 'New')

    expect(http.put).toHaveBeenCalledWith(`playlist-folders/${folder.id}`, { name: 'New' })
    expect(playlistFolderStore.byId(folder.id)!.name).toBe('New')
  })

  it('moves a playlist into a folder', async () => {
    const folder = h.factory('playlist-folder').make()
    const playlist = h.factory('playlist').make({ folder_id: null })
    vi.mocked(http.post).mockResolvedValue({})

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

    await playlistFolderStore.movePlaylistToFolder(playlist, toFolder)

    expect(playlist.folder_id).toBe(toFolder.id)
    expect(http.post).toHaveBeenCalledWith(`playlist-folders/${toFolder.id}/playlists`, { playlists: [playlist.id] })
    expect(http.delete).not.toHaveBeenCalled()
  })

  it('no-ops when the playlist is already in the target folder', async () => {
    const folder = h.factory('playlist-folder').make()
    const playlist = h.factory('playlist').make({ folder_id: folder.id })

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
