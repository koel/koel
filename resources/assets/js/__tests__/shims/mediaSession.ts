// jsdom implements neither navigator.mediaSession nor MediaMetadata, so the playback code that
// reports now-playing info to the OS bails out early and cannot be asserted on. Install the
// minimal surface that code touches.
class MediaMetadataShim {
  public title = ''
  public artist = ''
  public album = ''
  public artwork: MediaImage[] = []

  constructor(init: MediaMetadataInit = {}) {
    Object.assign(this, init)
  }
}

Object.defineProperty(window, 'MediaMetadata', { configurable: true, value: MediaMetadataShim })

Object.defineProperty(navigator, 'mediaSession', {
  configurable: true,
  value: {
    metadata: null,
    playbackState: 'none',
    setActionHandler: () => {},
    setPositionState: () => {},
  },
})
