import { arrayify } from '@/utils/helpers'
import { useAuthorization } from '@/composables/useAuthorization'
import { useKoelPlus } from '@/composables/useKoelPlus'

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

    editPlaylist: (playlist: Playlist) => playlist.user_id === currentUser.value.id,
    uploadSongs: () => isAdmin.value || isPlus.value,
    changeAlbumOrArtistThumbnails: () => isAdmin.value || isPlus.value, // for Plus, the logic is handled in the backend
  }

  currentUserCan.editSongs = currentUserCan.editSong

  return {
    currentUserCan,
  }
}
