import { http } from '@/services'

export const playlistCollaborationService = {
  async createInviteLink (playlist: Playlist) {
    if (playlist.is_smart) {
      throw Error('Smart playlists are not collaborative.')
    }

    const token = (await http.post<{ token: string }>(`playlists/${playlist.id}/collaborators/invite`)).token
    return `${window.location.origin}/#/playlist/collaborate/${token}`
  },

  async acceptInvite (token: string) {
    return http.post<Playlist>(`playlists/collaborators/accept`, { token })
  }
}
