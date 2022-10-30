export const audioService = {
  unlocked: false,
  context: null as unknown as AudioContext,
  source: null as unknown as MediaElementAudioSourceNode,
  element: null as unknown as HTMLMediaElement,

  init (element: HTMLMediaElement) {
    this.context = new AudioContext()
    this.source = this.context.createMediaElementSource(element)
    this.element = element

    this.unlockAudioContext()
  },

  getContext () {
    return this.context
  },

  getSource () {
    return this.source
  },

  getElement () {
    return this.element
  },

  /**
   * Attempt to unlock the audio context on mobile devices by creating and playing a silent buffer upon the
   * first user interaction.
   */
  unlockAudioContext () {
    ['touchend', 'touchstart', 'click'].forEach(event => {
      document.addEventListener(event, () => {
        if (this.unlocked) return

        const source = this.context.createBufferSource()
        source.buffer = this.context.createBuffer(1, 1, 22050)
        source.connect(this.context.destination)
        source.start(0)

        this.unlocked = true
      }, {
        once: true
      })
    })
  }
}
