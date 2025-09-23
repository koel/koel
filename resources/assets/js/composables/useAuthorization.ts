import { toRef } from 'vue'
import { userStore } from '@/stores/userStore'

export const useAuthorization = () => {
  return {
    currentUser: toRef(userStore.state, 'current'),
  }
}
