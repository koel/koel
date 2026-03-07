import type { Component } from 'vue'
import { ModalKey } from '@/config/symbols'
import { requireInjection } from '@/utils/helpers'
import type { Modals } from '@/config/modals'

export const useModal = () => {
  const modalOptions = requireInjection(ModalKey)

  const openModal = <K extends keyof Modals = never>(
    modal: Component,
    ...args: [Modals[K]] extends [never] ? [] : [props: Modals[K]]
  ) => {
    modalOptions.value = {
      component: modal,
      props: (args[0] ?? {}) as Record<string, any>,
    }
  }

  const closeModal = () => {
    modalOptions.value = {
      component: null,
    }
  }

  return {
    openModal,
    closeModal,
  }
}
