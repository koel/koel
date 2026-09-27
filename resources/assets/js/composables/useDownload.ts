import { downloadService } from '@/services/downloadService'
import { ZipInProgressError, ZipTooLargeError, ZipUnsupportedError } from '@/services/zipDownloadService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'

export const useDownload = () => {
  const { toastError } = useMessageToaster()
  const { handleHttpError } = useErrorHandler()

  const handle = async (fn: () => Promise<void>) => {
    try {
      await fn()
    } catch (error) {
      if (
        error instanceof ZipTooLargeError ||
        error instanceof ZipUnsupportedError ||
        error instanceof ZipInProgressError
      ) {
        toastError(error.message)
        return
      }

      handleHttpError(error)
    }
  }

  const fromPlayables = (playables: MaybeArray<Playable>) => handle(() => downloadService.fromPlayables(playables))
  const fromAlbum = (album: Album) => handle(() => downloadService.fromAlbum(album))
  const fromArtist = (artist: Artist) => handle(() => downloadService.fromArtist(artist))
  const fromPlaylist = (playlist: Playlist) => handle(() => downloadService.fromPlaylist(playlist))
  const fromFavorites = () => handle(() => downloadService.fromFavorites())

  return { fromPlayables, fromAlbum, fromArtist, fromPlaylist, fromFavorites }
}
