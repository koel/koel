const iOS = () => [
    'iPad Simulator',
    'iPhone Simulator',
    'iPod Simulator',
    'iPad',
    'iPhone',
    'iPod'
  ].includes(navigator.platform)
  // iPad on iOS 13 detection
  || (navigator.userAgent.includes("Mac") && "ontouchend" in document)

/**
 * Check if AudioContext is supported by the current browser.
 * Notice that event though iOS technically supports AudioContext, turning it on will cause a problem
 * where switching to another app will pause script execution and mute the audio.
 */
export const isAudioContextSupported = process.env.NODE_ENV !== 'test' && !iOS()

/**
 * Checks if the browser supports reading (and thus uploading) a whole directory.
 */
export const isDirectoryReadingSupported = window.DataTransferItem &&
  typeof window.DataTransferItem.prototype.webkitGetAsEntry === 'function'

export const isFullscreenSupported = () => Boolean(document.fullscreenEnabled)
