import { toRef } from 'vue'
import { commonStore } from '@/stores'

export const useThirdPartyServices = () => {
  const useLastfm = toRef(commonStore.state, 'useLastfm')
  const useYouTube = toRef(commonStore.state, 'useYouTube')
  const useiTunes = toRef(commonStore.state, 'useiTunes')

  return {
    useLastfm,
    useYouTube,
    useiTunes
  }
}
