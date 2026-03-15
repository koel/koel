import type { Ref } from 'vue'
import { onBeforeUnmount, ref, watch } from 'vue'

import ToTopButton from '@/components/ui/BtnScrollToTop.vue'

export const useInfiniteScroll = (container: Ref<HTMLElement | undefined>, loadMore: Closure) => {
  const sentinel = ref<HTMLElement>()
  let observer: IntersectionObserver | undefined

  watch(
    sentinel,
    (el, _, onCleanup) => {
      if (!el) {
        return
      }

      observer = new IntersectionObserver(
        entries => {
          if (entries[0].isIntersecting) {
            loadMore()
          }
        },
        {
          root: container.value,
          rootMargin: '100px',
        },
      )

      observer.observe(el)

      onCleanup(() => observer?.disconnect())
    },
    { flush: 'post' },
  )

  onBeforeUnmount(() => observer?.disconnect())

  return {
    ToTopButton,
    sentinel,
  }
}
