import isMobile from 'ismobilejs'

/**
 * Check if AudioContext is supported by the current browser.
 */
export const isAudioContextSupported: boolean = ((): boolean => {
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
  if (!(new ContextClass()).createMediaElementSource) {
    return false
  }

  return true
})()

/**
 * Checks if HTML5 clipboard can be used.
 */
export const isClipboardSupported: boolean = 'execCommand' in document

/**
 * Checks if Media Session API is supported.
 */
export const isMediaSessionSupported: boolean = 'mediaSession' in navigator

/**
 * Checks if the browser supports reading (and thus uploading) a whole directory.
 */
export const isDirectoryReadingSupported: boolean = window.DataTransferItem &&
  typeof window.DataTransferItem.prototype.webkitGetAsEntry === 'function'
