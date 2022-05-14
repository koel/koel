export const audioService = {
  context: null as unknown as AudioContext,
  source: null as unknown as MediaElementAudioSourceNode,
  element: null as unknown as HTMLMediaElement,

  init (element: HTMLMediaElement) {
    const AudioContext = window.AudioContext ||
      window.webkitAudioContext ||
      window.mozAudioContext ||
      window.oAudioContext ||
      window.msAudioContext

    this.context = new AudioContext()
    this.source = this.context.createMediaElementSource(element)
    this.element = element
  },

  getContext () {
    return this.context
  },

  getSource () {
    return this.source
  },

  getElement () {
    return this.element
  }
}
