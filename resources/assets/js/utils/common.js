/**
 * Other common methods.
 */
import select from 'select'
import { event } from '../utils'

/**
 * Load (display) a main panel (view).
 *
 * @param {String} view   The view, which can be found under components/main-wrapper/main-content.
 * @param {...*}      Extra data to attach to the view.
 */
export function loadMainView (view, ...args) {
  event.emit('main-content-view:load', view, ...args)
}

/**
 * Force reloading window regardless of "Confirm before reload" setting.
 * This is handy for certain cases, for example Last.fm connect/disconnect.
 */
export function forceReloadWindow () {
  window.onbeforeunload = function () {}
  window.location.reload()
}

/**
 * Show the overlay.
 *
 * @param  {String}  message
 * @param  {String}  type
 * @param  {Boolean} dismissable
 */
export function showOverlay (message = 'Just a little patience…', type = 'loading', dismissable = false) {
  event.emit('overlay:show', { message, type, dismissable })
}

/**
 * Hide the overlay.
 */
export function hideOverlay () {
  event.emit('overlay:hide')
}

/**
 * Copy a text into clipboard.
 *
 * @param  {string} txt
 */
export function copyText (txt) {
  const copyArea = document.querySelector('#copyArea')
  copyArea.style.top = `${window.pageYOffset || document.documentElement.scrollTop}px`
  copyArea.value = txt
  select(copyArea)
  document.execCommand('copy')
}
