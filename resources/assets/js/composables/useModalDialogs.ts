import { computed, ref } from 'vue'

const openDialogs = ref<HTMLDialogElement[]>([])

export const useModalDialogs = () => {
  const showModalDialog = (dialog: HTMLDialogElement) => {
    dialog.showModal()
    openDialogs.value = [...openDialogs.value.filter(openDialog => openDialog !== dialog), dialog]

    dialog.addEventListener(
      'close',
      () => (openDialogs.value = openDialogs.value.filter(openDialog => openDialog !== dialog)),
      { once: true },
    )
  }

  const topDialog = computed(() => openDialogs.value.at(-1))

  return {
    showModalDialog,
    topDialog,
  }
}
