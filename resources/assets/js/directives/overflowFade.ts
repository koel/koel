import type { Directive } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'

const toggleClasses = (el: HTMLElement) => {
  el.classList.toggle('fade-top', el.scrollTop !== 0)
  el.classList.toggle('fade-bottom', el.scrollTop + el.clientHeight !== el.scrollHeight)
}

export const overflowFade: Directive = {
  mounted: async (el: HTMLElement) => {
    const update = () => requestAnimationFrame(() => toggleClasses(el))

    // Catch content changes (async-loaded children) that make the element scrollable
    const mutationObserver = new MutationObserver(update)
    mutationObserver.observe(el, { childList: true, subtree: true })

    // Catch size changes on the element itself and its children
    const resizeObserver = new ResizeObserver(update)
    resizeObserver.observe(el)

    useIntersectionObserver(el, ([{ isIntersecting }]) => isIntersecting && toggleClasses(el))
    el.addEventListener('scroll', update)
  },
}
