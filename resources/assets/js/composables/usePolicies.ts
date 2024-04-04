import { useAuthorization, useKoelPlus } from '@/composables'
import { arrayify } from '@/utils'

export const usePolicies = () => {
  const { currentUser, isAdmin } = useAuthorization()
  const { isPlus } = useKoelPlus()

  const currentUserCan = {
    editSong: (song: Song | Song[]) => {
      if (isAdmin.value) return true
      if (!isPlus.value) return false
      return arrayify(song).every(s => s.owner_id === currentUser.value.id)
    },

    editPlaylist: (playlist: Playlist) => playlist.user_id === currentUser.value.id,

    uploadSongs: () => isAdmin.value || isPlus.value,

    changeAlbumOrArtistThumbnails: () => isAdmin.value || isPlus.value // for Plus, the logic is handled in the backend
  }

  currentUserCan['editSongs'] = currentUserCan.editSong

  return {
    currentUserCan
  }
}
