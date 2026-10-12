import { inject, onBeforeUnmount, watch } from 'vue'
import { ScrollAwayHeaderKey } from '@/config/symbols'

export const useScreenHeaderScroller = (getScroller: () => HTMLElement | null | undefined) => {
  const screenHeader = inject(ScrollAwayHeaderKey, null)

  if (!screenHeader) {
    return { carriesScreenHeader: false }
  }

  const visibilityObserver = new IntersectionObserver(entries =>
    entries
      .filter(({ isIntersecting }) => isIntersecting)
      .forEach(({ target }) => screenHeader.activateScroller(target as HTMLElement)),
  )

  watch(
    getScroller,
    (scroller, previous) => {
      if (previous) {
        visibilityObserver.unobserve(previous)
        screenHeader.detachScroller(previous)
      }

      if (scroller) {
        screenHeader.attachScroller(scroller)
        visibilityObserver.observe(scroller)
      }
    },
    { flush: 'post', immediate: true },
  )

  onBeforeUnmount(() => {
    visibilityObserver.disconnect()

    const scroller = getScroller()
    scroller && screenHeader.detachScroller(scroller)
  })

  return { carriesScreenHeader: true }
}
