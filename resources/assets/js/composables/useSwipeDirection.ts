import { onBeforeUnmount, onMounted } from 'vue'
import { throttle } from 'lodash'

export type SwipeDirection = 'up' | 'down'

export function useSwipeDirection (
  target: HTMLElement | (() => HTMLElement | null | undefined),
  callback: (direction: SwipeDirection) => void,
  options?: { threshold?: number, wheelThreshold?: number },
) {
  const threshold = options?.threshold ?? 30
  const wheelThreshold = options?.wheelThreshold ?? 5

  let startY = 0

  const getEl = () => (typeof target === 'function' ? target() : target)

  const onTouchStart = (e: TouchEvent) => {
    startY = e.touches[0].clientY
  }

  const onTouchEnd = (e: TouchEvent) => {
    const endY = e.changedTouches[0].clientY
    const deltaY = endY - startY
    if (Math.abs(deltaY) >= threshold) {
      callback(deltaY > 0 ? 'down' : 'up')
    }
  }

  const onWheel = throttle((e: WheelEvent) => {
    if (Math.abs(e.deltaY) < wheelThreshold) {
      return
    }
    callback(e.deltaY > 0 ? 'down' : 'up')
  }, 50)

  onMounted(() => {
    const el = getEl()
    if (!el) {
      return
    }

    el.addEventListener('touchstart', onTouchStart, { passive: true })
    el.addEventListener('touchend', onTouchEnd, { passive: true })
    el.addEventListener('wheel', onWheel, { passive: true })
  })

  onBeforeUnmount(() => {
    const el = getEl()
    if (!el) {
      return
    }

    el.removeEventListener('touchstart', onTouchStart)
    el.removeEventListener('touchend', onTouchEnd)
    el.removeEventListener('wheel', onWheel)
  })
}
