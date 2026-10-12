import { computed } from 'vue'
import { commonStore } from '@/stores/commonStore'

let lemonSqueezyLoaded: Promise<void> | null = null

const loadLemonSqueezy = () => {
  lemonSqueezyLoaded ??= new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = 'https://app.lemonsqueezy.com/js/lemon.js'
    script.onload = () => {
      window.createLemonSqueezy?.()
      resolve()
    }
    script.onerror = () => reject(new Error('Failed to load Lemon Squeezy'))
    document.head.appendChild(script)
  })

  return lemonSqueezyLoaded
}

export const useKoelPlus = () => {
  return {
    isPlus: computed(() => commonStore.state.koel_plus.active),
    license: {
      shortKey: commonStore.state.koel_plus.short_key,
      customerName: commonStore.state.koel_plus.customer_name,
      customerEmail: commonStore.state.koel_plus.customer_email,
    },
    checkoutUrl: computed(
      () => `https://store.koel.dev/checkout/buy/${commonStore.state.koel_plus.product_id}?embed=1&media=0&desc=0`,
    ),
    loadLemonSqueezy,
  }
}
