import { requireInjection } from '@/utils'
import { ModalContextKey } from '@/symbols'
import { Ref } from 'vue'

export const useModal = () => {
  const [modalContext] = requireInjection<[Ref<Record<string, any>>]>(ModalContextKey)

  return {
    getFromContext: <T> (key: string) => modalContext.value[key] as T
  }
}
