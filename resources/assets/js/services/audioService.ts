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
  normalizationGainNode: null! as GainNode,
  crossfadeSource: null as MediaElementAudioSourceNode | null,
  sourcesByElement: new WeakMap<HTMLMediaElement, MediaElementAudioSourceNode>(),
  crossfadeGainNode: null as GainNode | null,
  preampGainNode: null! as GainNode,
  analyzer: null! as AnalyserNode,

  bands: [] as Band[],
  preamp: 0,

  init(mediaElement: HTMLMediaElement) {
    this.element = mediaElement

    this.context = new AudioContext()
    this.normalizationGainNode = this.context.createGain()
    this.preampGainNode = this.context.createGain()
    this.source = this.getOrCreateSource(this.element)
    this.analyzer = this.context.createAnalyser()

    this.source.connect(this.normalizationGainNode)
    this.normalizationGainNode.connect(this.preampGainNode)

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
      filter.gain.value = config.gains[i]

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

  reconnectSource(newElement: HTMLMediaElement) {
    try {
      this.source.disconnect()
    } catch {
      // may already be disconnected
    }

    this.element = newElement

    if (this.crossfadeSource?.mediaElement === newElement) {
      this.source = this.crossfadeSource
      this.crossfadeSource = null
      this.disconnectCrossfadeElement()
    } else {
      this.source = this.getOrCreateSource(newElement)
    }

    this.source.connect(this.normalizationGainNode)
  },

  /**
   * Make the given element the one heard through the audio graph, if it isn't already.
   */
  routeElement(element: HTMLMediaElement) {
    if (this.context && this.element !== element) {
      this.reconnectSource(element)
    }
  },

  /** An element can only ever get one source node, so reuse it when the element is routed again. */
  getOrCreateSource(element: HTMLMediaElement) {
    let source = this.sourcesByElement.get(element)

    if (!source) {
      source = this.context.createMediaElementSource(element)
      this.sourcesByElement.set(element, source)
    }

    return source
  },

  /**
   * Route a crossfade's incoming element through the equalizer, fading it in on the audio clock to its own
   * normalization gain until it takes over.
   */
  connectCrossfadeElement(element: HTMLMediaElement, normalizationGainDb: number, fadeInDuration: number) {
    this.disconnectCrossfadeElement()

    const now = this.context.currentTime

    this.crossfadeGainNode = this.context.createGain()
    this.crossfadeGainNode.gain.setValueAtTime(0, now)
    this.crossfadeGainNode.gain.linearRampToValueAtTime(dbToGain(normalizationGainDb), now + fadeInDuration)
    this.crossfadeGainNode.connect(this.preampGainNode)

    this.crossfadeSource = this.getOrCreateSource(element)
    this.crossfadeSource.connect(this.crossfadeGainNode)
  },

  disconnectCrossfadeElement() {
    this.crossfadeSource?.disconnect()
    this.crossfadeGainNode?.disconnect()
    this.crossfadeSource = null
    this.crossfadeGainNode = null
  },

  changePreampGain(db: number) {
    this.preamp = db
    this.preampGainNode.gain.value = dbToGain(db)
  },

  changeNormalizationGain(db: number) {
    this.normalizationGainNode.gain.value = dbToGain(db)
  },

  /** A BiquadFilterNode's gain is already in dB, unlike a GainNode's. */
  changeFilterGain(node: BiquadFilterNode, db: number) {
    node.gain.value = db
  },

  /**
   * Attempt to unlock the audio context on mobile devices by creating and playing a silent buffer upon the
   * first user interaction.
   */
  unlockAudioContext() {
    ;['touchend', 'touchstart', 'click'].forEach(event => {
      document.addEventListener(
        event,
        () => {
          if (this.unlocked) {
            return
          }

          const source = this.context.createBufferSource()
          source.buffer = this.context.createBuffer(1, 1, 22050)
          source.connect(this.context.destination)
          source.start(0)

          this.unlocked = true
        },
        {
          once: true,
        },
      )
    })
  },
}
