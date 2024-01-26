import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { cache, http } from '@/services'
import { playlistCollaborationService as service } from './playlistCollaborationService'

new class extends UnitTestCase {
  protected test () {
    it('creates invite link', async () => {
      const playlist = factory<Playlist>('playlist', { is_smart: false })
      const postMock = this.mock(http, 'post').mockResolvedValue({ token: 'abc123' })

      const link = await service.createInviteLink(playlist)

      expect(postMock).toHaveBeenCalledWith(`playlists/${playlist.id}/collaborators/invite`)
      expect(link).toBe('http://localhost:3000/#/playlist/collaborate/abc123')
    })

    it('throws if trying to create invite link for smart playlist', async () => {
      const playlist = factory<Playlist>('playlist', { is_smart: true })

      await expect(service.createInviteLink(playlist)).rejects.toThrow('Smart playlists are not collaborative.')
    })

    it('accepts invite', async () => {
      const postMock = this.mock(http, 'post').mockResolvedValue({})

      await service.acceptInvite('abc123')

      expect(postMock).toHaveBeenCalledWith(`playlists/collaborators/accept`, { token: 'abc123' })
    })

    it('fetches collaborators', async () => {
      const playlist = factory<Playlist>('playlist')
      const collaborators = factory<PlaylistCollaborator[]>('playlist-collaborator', 2)
      const getMock = this.mock(http, 'get').mockResolvedValue(collaborators)

      const received = await service.fetchCollaborators(playlist)

      expect(getMock).toHaveBeenCalledWith(`playlists/${playlist.id}/collaborators`)
      expect(received).toBe(collaborators)
    })

    it('removes collaborator', async () => {
      const playlist = factory<Playlist>('playlist')
      const collaborator = factory<PlaylistCollaborator>('playlist-collaborator')
      const deleteMock = this.mock(http, 'delete').mockResolvedValue({})
      const removeCacheMock = this.mock(cache, 'remove')

      await service.removeCollaborator(playlist, collaborator)

      expect(deleteMock).toHaveBeenCalledWith(`playlists/${playlist.id}/collaborators`, {
        collaborator:
        collaborator.id
      })

      expect(removeCacheMock).toHaveBeenCalledWith(['playlist.songs', playlist.id])
    })
  }
}
