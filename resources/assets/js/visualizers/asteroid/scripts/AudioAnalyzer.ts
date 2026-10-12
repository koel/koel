import { configureAnalyser } from '@/visualizers/analyser'
import { splitIntoFrequencyBands } from './frequencyBands'

export class AudioAnalyzer {
  private bass = 0.0
  private mid = 0.0
  private high = 0.0
  private level = 0.0
  private history = 0.0
  private frame = 0

  private readonly frequencyBinCount: number
  private readonly binWidthHz: number

  // [!] this can't be read-only regardless of IDE's suggestion
  // noinspection TypeScriptFieldCanBeMadeReadonly
  private audioBuffer: Uint8Array

  private analyzer: AnalyserNode

  constructor () {
    this.analyzer = configureAnalyser({ fftSize: 1024 })
    this.frequencyBinCount = this.analyzer.frequencyBinCount
    this.binWidthHz = this.analyzer.context.sampleRate / this.analyzer.fftSize
    this.audioBuffer = new Uint8Array(this.frequencyBinCount)
  }

  update () {
    this.analyzer.getByteFrequencyData(this.audioBuffer)
    let bass = 0.0; let mid = 0.0; let high = 0.0

    if (!this.audioBuffer.some(value => value > 0)) {
      // create a "pulse" effect on audio idle
      if (this.frame % 40 == (Math.floor(Math.random() * 40.0))) {
        bass = Math.random()
        mid = Math.random()
        high = Math.random()
      }
    } else {
      const levels = Array.from(this.audioBuffer, value => (value / 196.0) ** 3.0)
      const bands = splitIntoFrequencyBands(levels, this.binWidthHz)

      bass = bands.bass
      mid = bands.mid
      high = bands.high
    }

    this.bass = this.bass > bass ? this.bass * 0.96 : bass
    this.mid = this.mid > mid ? this.mid * 0.96 : mid
    this.high = this.high > high ? this.high * 0.96 : high

    this.level = (this.bass + this.mid + this.high) / 3.0

    this.history += this.level * 0.01 + 0.005

    this.frame++
  }

  getBass () {
    return this.bass || 0.0
  }

  getMid () {
    return this.mid || 0.0
  }

  getHigh () {
    return this.high || 0.0
  }

  getLevel () {
    return this.level || 0.0
  }

  getHistory () {
    return this.history || 0.0
  }
}
