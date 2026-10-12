import { computed, onBeforeUnmount, readonly, ref, shallowReactive, watch } from 'vue'
import type { Ref } from 'vue'

const REVEAL_DISTANCE = 10
const SLIDE_DURATION = 200

export const useScrollAwayHeader = (host: Ref<HTMLElement | undefined>) => {
  const scrollers = shallowReactive(new Set<HTMLElement>())
  const lastScrollTops = new WeakMap<HTMLElement, number>()
  const scrollTop = ref(0)
  const revealed = ref(false)
  const sliding = ref(false)
  const expandedHeight = ref(0)
  const currentHeight = ref(0)

  let activeScroller: HTMLElement | null = null
  let upwardDistance = 0
  let slideTimer: ReturnType<typeof setTimeout> | undefined

  const setRevealed = (value: boolean) => {
    if (revealed.value === value) {
      return
    }

    revealed.value = value
    sliding.value = true
    clearTimeout(slideTimer)
    slideTimer = setTimeout(() => (sliding.value = false), SLIDE_DURATION)
  }

  const getClampedScrollTop = (scroller: HTMLElement) =>
    Math.min(Math.max(scroller.scrollTop, 0), Math.max(scroller.scrollHeight - scroller.clientHeight, 0))

  const activateScroller = (scroller: HTMLElement) => {
    if (activeScroller === scroller) {
      return
    }

    activeScroller = scroller
    upwardDistance = 0
    revealed.value = false
    sliding.value = false
    clearTimeout(slideTimer)
    scrollTop.value = getClampedScrollTop(scroller)
    lastScrollTops.set(scroller, scrollTop.value)
  }

  const onScroll = (event: Event) => {
    const scroller = event.currentTarget as HTMLElement
    activateScroller(scroller)

    const top = getClampedScrollTop(scroller)
    const delta = top - (lastScrollTops.get(scroller) ?? 0)

    lastScrollTops.set(scroller, top)
    scrollTop.value = top

    if (top <= 0 || delta > 0) {
      upwardDistance = 0
      setRevealed(false)
      return
    }

    upwardDistance -= delta

    if (top > expandedHeight.value && upwardDistance >= REVEAL_DISTANCE) {
      setRevealed(true)
    }
  }

  const resizeObserver = new ResizeObserver(([entry]) => {
    const height = entry.borderBoxSize[0].blockSize
    currentHeight.value = height

    if (!revealed.value) {
      expandedHeight.value = height
    }
  })

  watch(host, (element, previous) => {
    previous && resizeObserver.unobserve(previous)
    element && resizeObserver.observe(element)
  })

  const attachScroller = (scroller: HTMLElement) => {
    lastScrollTops.set(scroller, scroller.scrollTop)
    scroller.addEventListener('scroll', onScroll, { passive: true })
    scrollers.add(scroller)
  }

  const detachScroller = (scroller: HTMLElement) => {
    scroller.removeEventListener('scroll', onScroll)
    scrollers.delete(scroller)

    if (activeScroller === scroller) {
      activeScroller = null
    }

    if (!scrollers.size) {
      scrollTop.value = 0
      revealed.value = false
    }
  }

  const hiddenHeight = computed(() => (revealed.value ? 0 : Math.min(scrollTop.value, expandedHeight.value)))

  const columnHeaderTop = computed(() =>
    revealed.value ? currentHeight.value : Math.max(0, expandedHeight.value - scrollTop.value),
  )

  onBeforeUnmount(() => {
    scrollers.forEach(detachScroller)
    resizeObserver.disconnect()
    clearTimeout(slideTimer)
  })

  return {
    attached: computed(() => scrollers.size > 0),
    scrollerCount: computed(() => scrollers.size),
    hiddenHeight,
    api: {
      attachScroller,
      detachScroller,
      activateScroller,
      expandedHeight: readonly(expandedHeight),
      columnHeaderTop,
      revealed: readonly(revealed),
      sliding: readonly(sliding),
    },
  }
}
