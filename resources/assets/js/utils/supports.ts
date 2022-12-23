/**
 * Check if AudioContext is supported by the current browser.
 */
export const isAudioContextSupported = process.env.NODE_ENV !== 'test'

/**
 * Checks if the browser supports reading (and thus uploading) a whole directory.
 */
export const isDirectoryReadingSupported = window.DataTransferItem &&
  typeof window.DataTransferItem.prototype.webkitGetAsEntry === 'function'

export const isFullscreenSupported = () => Boolean(document.fullscreenEnabled)
