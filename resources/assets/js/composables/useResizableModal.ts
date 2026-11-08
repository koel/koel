import type { Ref } from 'vue'
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useEventListener, useMediaQuery } from '@vueuse/core'
import { clamp } from 'lodash-es'
import { useLocalStorage } from '@/composables/useLocalStorage'

interface ModalSize {
  width: number
  height: number
}

const VIEWPORT_MARGIN = 32

export const useResizableModal = (dialog: Ref<HTMLDialogElement | undefined>) => {
  const isDesktop = useMediaQuery('(min-width: 768px)')
  const { get, set } = useLocalStorage()

  const resizable = ref(false)

  let minSize: ModalSize = { width: 0, height: 0 }
  let storageKey = ''
  let content: Element | null | undefined = null

  const applySize = ({ width, height }: ModalSize) => {
    dialog.value!.style.width = `${clamp(width, minSize.width, Math.max(minSize.width, innerWidth - VIEWPORT_MARGIN))}px`
    dialog.value!.style.minHeight = `${clamp(height, minSize.height, Math.max(minSize.height, innerHeight - VIEWPORT_MARGIN))}px`
  }

  const disable = () => {
    resizable.value = false
    dialog.value?.style.removeProperty('width')
    dialog.value?.style.removeProperty('min-height')
  }

  const enableForForm = () => {
    if (dialog.value?.firstElementChild === content) {
      return
    }

    disable()

    content = dialog.value?.firstElementChild
    const formName = content instanceof HTMLFormElement && content.getAttribute('name')

    if (!isDesktop.value || !formName) {
      return
    }

    const { width, height } = dialog.value!.getBoundingClientRect()
    minSize = { width, height }
    storageKey = `modal-size.${formName}`

    applySize(get<ModalSize>(storageKey) ?? minSize)
    resizable.value = true
  }

  const startResizing = (event: PointerEvent) => {
    const grip = event.currentTarget as HTMLElement
    const { width, height } = dialog.value!.getBoundingClientRect()
    const start = { x: event.clientX, y: event.clientY, width, height }

    const resize = (moveEvent: PointerEvent) =>
      applySize({
        width: start.width + 2 * (moveEvent.clientX - start.x),
        height: start.height + 2 * (moveEvent.clientY - start.y),
      })

    const stop = () => {
      grip.removeEventListener('pointermove', resize)

      const { width: finalWidth, height: finalHeight } = dialog.value!.getBoundingClientRect()
      set(storageKey, { width: finalWidth, height: finalHeight })
    }

    grip.setPointerCapture(event.pointerId)
    grip.addEventListener('pointermove', resize)
    grip.addEventListener('lostpointercapture', stop, { once: true })
  }

  const contentObserver = new MutationObserver(enableForForm)

  onMounted(() => contentObserver.observe(dialog.value!, { childList: true }))

  onBeforeUnmount(() => contentObserver.disconnect())

  watch(isDesktop, () => {
    content = null
    enableForForm()
  })

  useEventListener(window, 'resize', () => resizable.value && applySize(get<ModalSize>(storageKey) ?? minSize))

  return {
    resizable,
    startResizing,
  }
}
