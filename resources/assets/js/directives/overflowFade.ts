import type { Directive } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'

const toggleClasses = (el: HTMLElement) => {
  el.classList.toggle('fade-top', el.scrollTop !== 0)
  el.classList.toggle('fade-bottom', el.scrollTop + el.clientHeight !== el.scrollHeight)
}

interface CleanupHandles {
  update: () => void
  stopIntersection: () => void
  mutationObserver: MutationObserver
  resizeObserver: ResizeObserver
}

const cleanupMap = new WeakMap<HTMLElement, CleanupHandles>()

export const overflowFade: Directive = {
  mounted: (el: HTMLElement) => {
    let rafId: number | null = null
    const update = () => {
      if (rafId !== null) return
      rafId = requestAnimationFrame(() => {
        toggleClasses(el)
        rafId = null
      })
    }

    // Catch content changes (async-loaded children) that make the element scrollable
    const mutationObserver = new MutationObserver(update)
    mutationObserver.observe(el, { childList: true, subtree: true })

    // Catch size changes on the element (including when children change the element's size)
    const resizeObserver = new ResizeObserver(update)
    resizeObserver.observe(el)

    const { stop: stopIntersection } = useIntersectionObserver(el, ([{ isIntersecting }]) => {
      if (isIntersecting) toggleClasses(el)
    })

    el.addEventListener('scroll', update, { passive: true })

    // Apply immediately in case the element is already scrollable
    requestAnimationFrame(() => toggleClasses(el))

    cleanupMap.set(el, { update, stopIntersection, mutationObserver, resizeObserver })
  },

  unmounted: (el: HTMLElement) => {
    const handles = cleanupMap.get(el)

    if (handles) {
      el.removeEventListener('scroll', handles.update)
      handles.stopIntersection()
      handles.mutationObserver.disconnect()
      handles.resizeObserver.disconnect()
      cleanupMap.delete(el)
    }
  },
}
