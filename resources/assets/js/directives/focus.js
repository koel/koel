/**
 * A simple directive to set focus into an input field when it's shown.
 */
export const focusDirective = {
  inserted (el) {
    el.focus()
  }
}
