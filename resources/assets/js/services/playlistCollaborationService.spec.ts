import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { cache } from '@/services/cache'
import { http } from '@/services/http'
import { playlistCollaborationService as service } from './playlistCollaborationService'

describe('playlistCollaborationService', () => {
  const h = createHarness()

  it('creates invite link', async () => {
    const playlist = h.factory('playlist', { is_smart: false })
    const postMock = h.mock(http, 'post').mockResolvedValue({ token: 'abc123' })

    const link = await service.createInviteLink(playlist)

    expect(postMock).toHaveBeenCalledWith(`playlists/${playlist.id}/collaborators/invite`)
    expect(link).toBe('http://localhost:3000/#/playlist/collaborate/abc123')
  })

  it('throws if trying to create invite link for smart playlist', async () => {
    const playlist = h.factory('playlist', { is_smart: true })

    await expect(service.createInviteLink(playlist)).rejects.toThrow('Smart playlists are not collaborative.')
  })

  it('accepts invite', async () => {
    const postMock = h.mock(http, 'post').mockResolvedValue({})

    await service.acceptInvite('abc123')

    expect(postMock).toHaveBeenCalledWith(`playlists/collaborators/accept`, { token: 'abc123' })
  })

  it('fetches collaborators', async () => {
    const playlist = h.factory('playlist')
    const collaborators = h.factory('playlist-collaborator', 2)
    const getMock = h.mock(http, 'get').mockResolvedValue(collaborators)

    const received = await service.fetchCollaborators(playlist)

    expect(getMock).toHaveBeenCalledWith(`playlists/${playlist.id}/collaborators`)
    expect(received).toBe(collaborators)
  })

  it('removes collaborator', async () => {
    const playlist = h.factory('playlist')
    const collaborator = h.factory('playlist-collaborator')
    const deleteMock = h.mock(http, 'delete').mockResolvedValue({})
    const removeCacheMock = h.mock(cache, 'remove')

    await service.removeCollaborator(playlist, collaborator)

    expect(deleteMock).toHaveBeenCalledWith(`playlists/${playlist.id}/collaborators`, {
      collaborator:
      collaborator.id,
    })

    expect(removeCacheMock).toHaveBeenCalledWith(['playlist.songs', playlist.id])
  })
})
