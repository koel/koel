import { Directive } from 'vue'

const toggleClasses = (el: HTMLElement) => {
  el.classList.toggle('fade-top', el.scrollTop !== 0)
  el.classList.toggle('fade-bottom', el.scrollTop + el.clientHeight !== el.scrollHeight)
}

const observeVisibility = (el: HTMLElement, callback: Closure) => {
  const observer = new IntersectionObserver(entries => entries.forEach(entry => entry.isIntersecting && callback()))
  observer.observe(el)
}

export const overflowFade: Directive = {
  mounted: async (el: HTMLElement) => {
    observeVisibility(el, () => toggleClasses(el))
    el.addEventListener('scroll', () => requestAnimationFrame(() => toggleClasses(el)))
  }
}
