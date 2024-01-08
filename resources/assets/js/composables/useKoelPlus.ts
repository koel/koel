import { computed, reactive } from 'vue'
import { commonStore } from '@/stores'

export const useKoelPlus = () => {
  return {
    isPlus: computed(() => commonStore.state.koel_plus.active),
    license: {
      shortKey: commonStore.state.koel_plus.short_key,
      customerName: commonStore.state.koel_plus.customer_name,
      customerEmail: commonStore.state.koel_plus.customer_email
    },
    storeUrl: computed(() => commonStore.state.koel_plus.store_url)
  }
}
