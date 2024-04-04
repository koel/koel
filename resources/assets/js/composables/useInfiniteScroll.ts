import { Ref } from 'vue'
import { useInfiniteScroll as baseUseInfiniteScroll } from '@vueuse/core'

import ToTopButton from '@/components/ui/BtnScrollToTop.vue'

export const useInfiniteScroll = (el: Ref<HTMLElement | undefined>, loadMore: Closure) => {
  baseUseInfiniteScroll(el, loadMore, { distance: 32 })

  let tries = 0
  const MAX_TRIES = 5

  const makeScrollable = async () => {
    const container = el.value

    if (!container) {
      window.setTimeout(() => makeScrollable(), 200)
      return
    }

    if (container.scrollHeight <= container.clientHeight && tries < MAX_TRIES) {
      tries++
      await loadMore()
      window.setTimeout(() => makeScrollable(), 200)
    }
  }

  return {
    ToTopButton,
    makeScrollable
  }
}
