import { cache } from '@/services/cache'
import { http } from '@/services/http'

export const playlistCollaborationService = {
  async createInviteLink (playlist: Playlist) {
    if (playlist.is_smart) {
      throw new Error('Smart playlists are not collaborative.')
    }

    const token = (await http.post<{ token: string }>(`playlists/${playlist.id}/collaborators/invite`)).token
    return `${window.location.origin}/#/playlist/collaborate/${token}`
  },

  async acceptInvite (token: string) {
    return http.post<Playlist>(`playlists/collaborators/accept`, { token })
  },

  async fetchCollaborators (playlist: Playlist) {
    return http.get<PlaylistCollaborator[]>(`playlists/${playlist.id}/collaborators`)
  },

  async removeCollaborator (playlist: Playlist, collaborator: PlaylistCollaborator) {
    await http.delete(`playlists/${playlist.id}/collaborators`, { collaborator: collaborator.id })
    // invalidate the playlist cache
    cache.remove(['playlist.songs', playlist.id])
  },
}
