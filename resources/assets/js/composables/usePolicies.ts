import { arrayify } from '@/utils/helpers'
import { useAuthorization } from '@/composables/useAuthorization'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { acl } from '@/services/acl'

export const usePolicies = () => {
  const { currentUser } = useAuthorization()
  const { isPlus } = useKoelPlus()

  const currentUserCan = {
    editSong: (songs: MaybeArray<Song>) => {
      if (currentUser.value.permissions.includes('manage songs')) {
        return true
      }

      if (!isPlus.value) {
        return false
      }

      return arrayify(songs).every(song => song.owner_id === currentUser.value.id)
    },

    editPlaylist: (playlist: Playlist) => playlist.owner_id === currentUser.value.id,
    editAlbum: async (album: Album) => await acl.checkResourcePermission('album', album.id, 'edit'),
    editArtist: async (artist: Artist) => await acl.checkResourcePermission('artist', artist.id, 'edit'),
    editUser: async (user: User) => await acl.checkResourcePermission('user', user.id, 'edit'),
    deleteUser: async (user: User) => await acl.checkResourcePermission('user', user.id, 'delete'),

    editRadioStation: async (station: RadioStation) => {
      return await acl.checkResourcePermission('radio-station', station.id, 'edit')
    },

    deleteRadioStation: async (station: RadioStation) => {
      return await acl.checkResourcePermission('radio-station', station.id, 'delete')
    },

    // If the user has the permission, they can always add a radio station, even in demo mode.
    addRadioStation: () => !window.IS_DEMO || currentUser.value.permissions.includes('manage radio stations'),

    manageSettings: () => currentUser.value.permissions.includes('manage settings'),
    manageUsers: () => currentUser.value.permissions.includes('manage users'),
    uploadSongs: () => isPlus.value || currentUser.value.permissions.includes('manage songs'),
  }

  return {
    currentUserCan,
  }
}
