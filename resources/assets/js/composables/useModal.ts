import { inject } from 'vue'
import { ModalContextKey } from '@/symbols'

export const useModal = () => {
  const modalContext = inject(ModalContextKey)

  return {
    getFromContext: <T> (key: string) => modalContext?.value[key] as T,
  }
}
