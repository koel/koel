import { toRef } from 'vue'
import { commonStore } from '@/stores'

export const useThirdPartyServices = () => {
  return {
    useLastfm: toRef(commonStore.state, 'use_last_fm'),
    useYouTube: toRef(commonStore.state, 'use_you_tube'),
    useAppleMusic: toRef(commonStore.state, 'use_i_tunes'),
    useSpotify: toRef(commonStore.state, 'use_spotify')
  }
}
