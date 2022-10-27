import { computed, onUnmounted, ref } from 'vue'

export const useNetworkStatus = () => {
  const online = ref(navigator.onLine)
  const offline = computed(() => !online.value)

  const updateOnlineStatus = () => (online.value = navigator.onLine)

  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)

  onUnmounted(() => {
    window.removeEventListener('online', updateOnlineStatus)
    window.removeEventListener('offline', updateOnlineStatus)
  })

  return {
    online,
    offline
  }
}
