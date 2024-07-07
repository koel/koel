import { Directive } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'

const toggleClasses = (el: HTMLElement) => {
  el.classList.toggle('fade-top', el.scrollTop !== 0)
  el.classList.toggle('fade-bottom', el.scrollTop + el.clientHeight !== el.scrollHeight)
}

export const overflowFade: Directive = {
  mounted: async (el: HTMLElement) => {
    useIntersectionObserver(el, ([{ isIntersecting }]) => isIntersecting && toggleClasses(el))
    el.addEventListener('scroll', () => requestAnimationFrame(() => toggleClasses(el)))
  }
}
