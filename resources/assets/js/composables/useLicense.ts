import { computed } from 'vue'
import { commonStore } from '@/stores'

export const useLicense = () => {
  return {
    isKoelPlus: computed(() => commonStore.state.koel_plus)
  }
}
