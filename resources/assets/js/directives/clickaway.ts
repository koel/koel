import { Directive } from 'vue'

export const clickaway: Directive = {
  created (el: HTMLElement, binding) {
    document.addEventListener('click', (e: MouseEvent) => el.contains(e.target as Node) || binding.value())
  }
}
