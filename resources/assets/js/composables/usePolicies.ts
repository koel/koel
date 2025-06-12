import { arrayify } from '@/utils/helpers'
import { useAuthorization } from '@/composables/useAuthorization'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { resourcePermissionService } from '@/services/resourcePermissionService'

export const usePolicies = () => {
  const { currentUser, isAdmin } = useAuthorization()
  const { isPlus } = useKoelPlus()

  const currentUserCan = {
    editSong: (songs: MaybeArray<Song>) => {
      if (isAdmin.value) {
        return true
      }

      if (!isPlus.value) {
        return false
      }

      return arrayify(songs).every(song => song.owner_id === currentUser.value.id)
    },

    // alias
    editSongs (songs: MaybeArray<Song>) {
      return this.editSong(songs)
    },

    editPlaylist: (playlist: Playlist) => playlist.owner_id === currentUser.value.id,
    uploadSongs: () => isAdmin.value || isPlus.value,
    editAlbum: async (album: Album) => await resourcePermissionService.check('album', album.id, 'edit'),
    editArtist: async (artist: Artist) => await resourcePermissionService.check('artist', artist.id, 'edit'),
  }

  return {
    currentUserCan,
  }
}
