import { useOnline } from '@vueuse/core'

/**
 * Singleton wrapper around VueUse's useOnline() so that all components
 * share the same reactive online/offline state.
 */
const online = useOnline()

export const useNetworkStatus = () => {
  return {
    online,
  }
}
