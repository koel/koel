import type { Ref } from 'vue'
import { onScopeDispose } from 'vue'
import { useInfiniteScroll as baseUseInfiniteScroll } from '@vueuse/core'

import ToTopButton from '@/components/ui/BtnScrollToTop.vue'

export const useInfiniteScroll = (el: Ref<HTMLElement | undefined>, loadMore: Closure) => {
  baseUseInfiniteScroll(el, loadMore, { distance: 32 })

  let tries = 0
  let timerId: ReturnType<typeof setTimeout> | undefined
  const MAX_TRIES = 5

  const makeScrollable = async () => {
    const container = el.value

    if (!container) {
      timerId = setTimeout(() => makeScrollable(), 200)
      return
    }

    if (container.scrollHeight <= container.clientHeight && tries < MAX_TRIES) {
      tries++
      await loadMore()
      timerId = setTimeout(() => makeScrollable(), 200)
    }
  }

  onScopeDispose(() => {
    if (timerId !== undefined) {
      clearTimeout(timerId)
    }
  })

  return {
    ToTopButton,
    makeScrollable,
  }
}
