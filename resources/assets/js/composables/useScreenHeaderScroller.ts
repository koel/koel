import { inject, onBeforeUnmount, watch } from 'vue'
import { ScrollAwayHeaderKey } from '@/config/symbols'

export const useScreenHeaderScroller = (getScroller: () => HTMLElement | null | undefined) => {
  const screenHeader = inject(ScrollAwayHeaderKey, null)

  if (!screenHeader) {
    return { carriesScreenHeader: false }
  }

  watch(
    getScroller,
    (scroller, previous) => {
      previous && screenHeader.detachScroller(previous)
      scroller && screenHeader.attachScroller(scroller)
    },
    { flush: 'post', immediate: true },
  )

  onBeforeUnmount(() => {
    const scroller = getScroller()
    scroller && screenHeader.detachScroller(scroller)
  })

  return { carriesScreenHeader: true }
}
