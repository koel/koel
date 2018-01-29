/**
 * A fork of https://github.com/simplesmiler/vue-clickaway.
 * Trigger a function if the user clicks out of the bound element.
 * @type {Object}
 */
export const clickawayDirective = {
  bind (el, { value }) {
    if (typeof value !== 'function') {
      console.warn(`Expect a function, got ${value}`)
      return
    }

    document.addEventListener('click', e => el.contains(e.target) || value())
  }
}
