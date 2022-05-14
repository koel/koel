import isMobile from 'ismobilejs'

/**
 * Check if AudioContext is supported by the current browser.
 */
export const isAudioContextSupported = (() => {
  if (process.env.NODE_ENV === 'test') {
    return false
  }

  // Apple devices just don't love AudioContext that much.
  if (isMobile.apple.device) {
    return false
  }

  const ContextClass = window.AudioContext ||
    window.webkitAudioContext ||
    window.mozAudioContext ||
    window.oAudioContext ||
    window.msAudioContext

  if (!ContextClass) {
    return false
  }

  // Safari (MacOS & iOS alike) has webkitAudioContext, but is buggy.
  // @link http://caniuse.com/#search=audiocontext
  return Boolean((new ContextClass()).createMediaElementSource)
})()

/**
 * Checks if HTML5 clipboard can be used.
 */
export const isClipboardSupported = 'execCommand' in document

/**
 * Checks if the browser supports reading (and thus uploading) a whole directory.
 */
export const isDirectoryReadingSupported = window.DataTransferItem &&
  typeof window.DataTransferItem.prototype.webkitGetAsEntry === 'function'
