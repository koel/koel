import { equalizerStore } from '@/stores/equalizerStore'
import { frequencies } from '@/config/audio'

export const dbToGain = (db: number) => 10 ** (db / 20) || 0

export interface Band {
  label: string
  node: BiquadFilterNode
  db: number
}

export const audioService = {
  unlocked: false,

  context: null! as AudioContext,
  source: null! as MediaElementAudioSourceNode,
  element: null! as HTMLMediaElement,
  preampGainNode: null! as GainNode,
  analyzer: null! as AnalyserNode,

  bands: [] as Band[],

  init (mediaElement: HTMLMediaElement) {
    this.element = mediaElement

    this.context = new AudioContext()
    this.preampGainNode = this.context.createGain()
    this.source = this.context.createMediaElementSource(this.element)
    this.analyzer = this.context.createAnalyser()

    this.source.connect(this.preampGainNode)

    const config = equalizerStore.getConfig()

    this.changePreampGain(config.preamp)

    let prevFilter: BiquadFilterNode

    // Create 10 bands with the frequencies similar to those of Winamp and connect them together.
    frequencies.forEach((frequency, i) => {
      const filter = this.context.createBiquadFilter()

      if (i === 0) {
        filter.type = 'lowshelf'
      } else if (i === frequencies.length - 1) {
        filter.type = 'highshelf'
      } else {
        filter.type = 'peaking'
      }

      filter.Q.setTargetAtTime(1, this.context.currentTime, 0.01)
      filter.frequency.setTargetAtTime(frequency, this.context.currentTime, 0.01)
      filter.gain.value = dbToGain(config.gains[i])

      prevFilter ? prevFilter.connect(filter) : this.preampGainNode.connect(filter)
      prevFilter = filter

      this.bands.push({
        node: filter,
        label: String(frequency).replace('000', 'K'),
        db: config.gains[i],
      })
    })

    prevFilter!.connect(this.analyzer)

    // connect the analyzer node last, so that changes to the equalizer affect the visualizer as well
    this.analyzer.connect(this.context.destination)

    this.unlockAudioContext()
  },

  changePreampGain (db: number) {
    this.preampGainNode.gain.value = dbToGain(db)
  },

  changeFilterGain (node: BiquadFilterNode, db: number) {
    node.gain.value = dbToGain(db)
  },

  /**
   * Attempt to unlock the audio context on mobile devices by creating and playing a silent buffer upon the
   * first user interaction.
   */
  unlockAudioContext () {
    ['touchend', 'touchstart', 'click'].forEach(event => {
      document.addEventListener(event, () => {
        if (this.unlocked) {
          return
        }

        const source = this.context.createBufferSource()
        source.buffer = this.context.createBuffer(1, 1, 22050)
        source.connect(this.context.destination)
        source.start(0)

        this.unlocked = true
      }, {
        once: true,
      })
    })
  },
}
