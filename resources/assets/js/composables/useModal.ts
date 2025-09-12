import type { Ref } from 'vue'
import { inject } from 'vue'
import { ModalContextKey } from '@/symbols'
import type { Modals } from '@/config/modals'

export const useModal = <N extends keyof Modals> () => {
  const modalContext = inject<Ref<Modals[N]>>(ModalContextKey)

  const getFromContext = <K extends keyof Modals[N]> (key: K): Modals[N][K] => {
    if (!modalContext || !Object.prototype.hasOwnProperty.call(modalContext.value, key)) {
      throw new Error(`Modal context does not have a value for ${String(key)}`)
    }

    return modalContext.value[key]
  }

  return {
    getFromContext,
  }
}
