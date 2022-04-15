export const audio = {
  context: null as AudioContext | null,
  source: null as MediaElementAudioSourceNode | null,
  element: null as HTMLMediaElement | null,

  init (element: HTMLMediaElement): void {
    const AudioContext = window.AudioContext ||
      window.webkitAudioContext ||
      window.mozAudioContext ||
      window.oAudioContext ||
      window.msAudioContext

    this.context = new AudioContext()
    this.source = this.context.createMediaElementSource(element)
    this.element = element
  },

  getContext (): AudioContext {
    return this.context!
  },

  getSource (): MediaElementAudioSourceNode {
    return this.source!
  },

  getElement (): HTMLMediaElement {
    return this.element!
  }
}
