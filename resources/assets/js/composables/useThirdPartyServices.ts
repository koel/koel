import { toRef } from 'vue'
import { commonStore } from '@/stores'

export const useThirdPartyServices = () => {
  const useLastfm = toRef(commonStore.state, 'use_last_fm')
  const useYouTube = toRef(commonStore.state, 'use_you_tube')
  const useAppleMusic = toRef(commonStore.state, 'use_i_tunes')

  return {
    useLastfm,
    useYouTube,
    useAppleMusic
  }
}
