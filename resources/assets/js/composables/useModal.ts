import type { Ref } from 'vue'
import { requireInjection } from '@/utils/helpers'
import { ModalContextKey } from '@/symbols'

export const useModal = () => {
  const [modalContext] = requireInjection<[Ref<Record<string, any>>]>(ModalContextKey)

  return {
    getFromContext: <T> (key: string) => modalContext.value[key] as T,
  }
}
