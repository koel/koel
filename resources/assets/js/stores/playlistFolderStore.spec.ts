import { describe, expect, it, vi } from 'vitest'
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
  createHarness({
    beforeEach: () => {
      playlistFolderStore.state.folders = []
      vi.mocked(http.post).mockReset()
      vi.mocked(http.put).mockReset()
      vi.mocked(http.delete).mockReset()
      vi.mocked(playlistStore.byFolder).mockReturnValue([])
    },
  })

  it('initializes with sorted folders', () => {
    playlistFolderStore.init([
      { id: '2', name: 'Zebra' } as PlaylistFolder,
      { id: '1', name: 'Alpha' } as PlaylistFolder,
    ])

    expect(playlistFolderStore.state.folders[0].name).toBe('Alpha')
    expect(playlistFolderStore.state.folders[1].name).toBe('Zebra')
  })

  it('finds folder by id', () => {
    playlistFolderStore.init([{ id: '1', name: 'Rock' } as PlaylistFolder, { id: '2', name: 'Jazz' } as PlaylistFolder])

    expect(playlistFolderStore.byId('2')!.name).toBe('Jazz')
  })

  it('returns undefined for non-existent id', () => {
    playlistFolderStore.init([])
    expect(playlistFolderStore.byId('999')).toBeUndefined()
  })

  it('stores a new folder via API and adds sorted', async () => {
    playlistFolderStore.init([{ id: '1', name: 'Alpha' } as PlaylistFolder])

    vi.mocked(http.post).mockResolvedValue({ id: '2', name: 'Beta' })

    const folder = await playlistFolderStore.store('Beta')

    expect(http.post).toHaveBeenCalledWith('playlist-folders', { name: 'Beta' })
    expect(folder.name).toBe('Beta')
    expect(playlistFolderStore.state.folders).toHaveLength(2)
    expect(playlistFolderStore.state.folders[0].name).toBe('Alpha')
    expect(playlistFolderStore.state.folders[1].name).toBe('Beta')
  })

  it('deletes a folder and unlinks playlists', async () => {
    const folder = { id: '1', name: 'Rock' } as PlaylistFolder
    playlistFolderStore.init([folder])

    const playlist = { folder_id: '1' } as Playlist
    vi.mocked(playlistStore.byFolder).mockReturnValue([playlist])
    vi.mocked(http.delete).mockResolvedValue({})

    await playlistFolderStore.delete(folder)

    expect(http.delete).toHaveBeenCalledWith('playlist-folders/1')
    expect(playlistFolderStore.state.folders).toHaveLength(0)
    expect(playlist.folder_id).toBeNull()
  })

  it('renames a folder', async () => {
    playlistFolderStore.init([{ id: '1', name: 'Old' } as PlaylistFolder])
    vi.mocked(http.put).mockResolvedValue({})

    await playlistFolderStore.rename({ id: '1', name: 'Old' } as PlaylistFolder, 'New')

    expect(http.put).toHaveBeenCalledWith('playlist-folders/1', { name: 'New' })
    expect(playlistFolderStore.byId('1')!.name).toBe('New')
  })

  it('adds a playlist to folder', async () => {
    const folder = { id: '1', name: 'Rock' } as PlaylistFolder
    const playlist = { id: 10, folder_id: null } as unknown as Playlist
    vi.mocked(http.post).mockResolvedValue({})

    await playlistFolderStore.addPlaylistToFolder(folder, playlist)

    expect(playlist.folder_id).toBe('1')
    expect(http.post).toHaveBeenCalledWith('playlist-folders/1/playlists', { playlists: [10] })
  })

  it('removes a playlist from folder', async () => {
    const folder = { id: '1', name: 'Rock' } as PlaylistFolder
    const playlist = { id: 10, folder_id: '1' } as unknown as Playlist
    vi.mocked(http.delete).mockResolvedValue({})

    await playlistFolderStore.removePlaylistFromFolder(folder, playlist)

    expect(playlist.folder_id).toBeNull()
    expect(http.delete).toHaveBeenCalledWith('playlist-folders/1/playlists', { playlists: [10] })
  })

  it('sorts folders alphabetically', () => {
    const sorted = playlistFolderStore.sort([
      { id: '3', name: 'Zebra' } as PlaylistFolder,
      { id: '1', name: 'Alpha' } as PlaylistFolder,
      { id: '2', name: 'Middle' } as PlaylistFolder,
    ])

    expect(sorted.map(f => f.name)).toEqual(['Alpha', 'Middle', 'Zebra'])
  })
})
