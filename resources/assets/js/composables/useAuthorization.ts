import { computed, toRef } from 'vue'
import { userStore } from '@/stores/userStore'

export const useAuthorization = () => {
  const currentUser = toRef(userStore.state, 'current')
  const isAdmin = computed(() => currentUser.value?.is_admin)

  return {
    currentUser,
    isAdmin,
  }
}
