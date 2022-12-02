import { Directive } from 'vue'

export const focus: Directive = {
  mounted: (el: HTMLElement) => el.focus()
}
