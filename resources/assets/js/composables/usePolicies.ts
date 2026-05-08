import { arrayify } from '@/utils/helpers'
import { useAuthorization } from '@/composables/useAuthorization'
import { useKoelPlus } from '@/composables/useKoelPlus'

export const usePolicies = () => {
  const { currentUser } = useAuthorization()
  const { isPlus } = useKoelPlus()

  const currentUserCan = {
    editSong: (songs: MaybeArray<Song>) => {
      if (currentUser.value.abilities.includes('manage songs')) {
        return true
      }

      if (!isPlus.value) {
        return false
      }

      return arrayify(songs).every(song => song.owner_id === currentUser.value.id)
    },

    editPlaylist: (playlist: Playlist) => playlist.permissions.edit,
    deletePlaylist: (playlist: Playlist) => playlist.permissions.delete,
    editAlbum: (album: Album) => album.permissions.edit,
    editArtist: (artist: Artist) => artist.permissions.edit,
    editUser: (user: User) => user.permissions.edit,
    deleteUser: (user: User) => user.permissions.delete,

    editRadioStation: (station: RadioStation) => station.permissions.edit,
    deleteRadioStation: (station: RadioStation) => station.permissions.delete,

    // If the user has the permission, they can always add a radio station, even in demo mode.
    addRadioStation: () => !window.IS_DEMO || currentUser.value.abilities.includes('manage radio stations'),

    manageSettings: () => currentUser.value.abilities.includes('manage settings'),
    manageUsers: () => currentUser.value.abilities.includes('manage users'),
    uploadSongs: () => {
      if (currentUser.value.abilities.includes('manage songs')) {
        return true
      }

      // On Plus, every user has their own library to upload to — except Guests, who don't.
      return isPlus.value && currentUser.value.role !== 'guest'
    },
  }

  return {
    currentUserCan,
  }
}
