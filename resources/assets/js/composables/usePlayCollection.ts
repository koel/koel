import { useMessageToaster } from '@/composables/useMessageToaster'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'

type PlayCollectionOptions = {
  shuffle?: boolean
  emptyMessage: string
}

export const usePlayCollection = () => {
  const { go, url } = useRouter()
  const { toastWarning } = useMessageToaster()

  const playCollection = async (
    fetchPlayables: () => Promise<Playable[]>,
    { shuffle = false, emptyMessage }: PlayCollectionOptions,
  ) => {
    const playables = await fetchPlayables()

    if (!playables.length) {
      toastWarning(emptyMessage)
      return
    }

    playback().queueAndPlay(playables, shuffle)
    go(url('queue'))
  }

  return {
    playCollection,
  }
}
