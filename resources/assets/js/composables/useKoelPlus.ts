import { computed } from 'vue'
import { commonStore } from '@/stores'

export const useKoelPlus = () => {
  return {
    isPlus: computed(() => commonStore.state.koel_plus.active),
    license: {
      shortKey: commonStore.state.koel_plus.short_key,
      customerName: commonStore.state.koel_plus.customer_name,
      customerEmail: commonStore.state.koel_plus.customer_email
    },
    checkoutUrl: computed(() =>
      `https://store.koel.dev/checkout/buy/${commonStore.state.koel_plus.product_id}?embed=1&media=0&desc=0`
    )
  }
}
