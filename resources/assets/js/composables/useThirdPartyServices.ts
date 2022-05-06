import { toRef } from 'vue'
import { commonStore } from '@/stores'

export const useThirdPartyServices = () => {
  const useLastfm = toRef(commonStore.state, 'useLastfm')
  const useYouTube = toRef(commonStore.state, 'useYouTube')
  const useAppleMusic = toRef(commonStore.state, 'useiTunes')

  return {
    useLastfm,
    useYouTube,
    useAppleMusic
  }
}
