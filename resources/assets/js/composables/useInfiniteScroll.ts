import { ref } from 'vue'
import ToTopButton from '@/components/ui/BtnScrollToTop.vue'

export const useInfiniteScroll = (loadMore: Closure) => {
  const scroller = ref<HTMLElement>()

  const scrolling = ({ target }: { target: HTMLElement }) => {
    // Here we check if the user has scrolled to the end of the wrapper (or 32px to the end).
    // If that's true, load more items.
    if (target.scrollTop + target.clientHeight >= target.scrollHeight - 32) {
      loadMore()
    }
  }

  let tries = 0
  const MAX_TRIES = 5

  const makeScrollable = async () => {
    const container = scroller.value

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
    scroller,
    scrolling,
    makeScrollable
  }
}
