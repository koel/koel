import { Directive } from 'vue'

/**
 * A simple directive to set focus into an input field when it's shown.
 */
export const focus: Directive = {
  mounted: (el: HTMLElement) => el.focus()
}
