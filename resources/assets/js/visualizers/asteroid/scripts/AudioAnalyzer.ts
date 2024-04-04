import { audioService } from '@/services'

export class AudioAnalyzer {
  private bass = 0.
  private mid = 0.
  private high = 0.
  private level = 0.
  private history = 0.
  private frame = 0

  private readonly frequencyBinCount: number

  // [!] this can't be read-only regardless of IDE's suggestion
  // noinspection TypeScriptFieldCanBeMadeReadonly
  private audioBuffer: Uint8Array

  private analyzer: AnalyserNode

  constructor () {
    this.analyzer = audioService.analyzer
    this.analyzer.fftSize = 128
    this.frequencyBinCount = this.analyzer.frequencyBinCount
    this.audioBuffer = new Uint8Array(this.frequencyBinCount)
  }

  update () {
    this.analyzer.getByteFrequencyData(this.audioBuffer)
    let bass = 0., mid = 0., high = 0.

    if (this.audioBuffer[0] === 0) {
      // create a "pulse" effect on audio idle
      if (this.frame % 40 == (Math.floor(Math.random() * 40.))) {
        bass = Math.random()
        mid = Math.random()
        high = Math.random()
      }
    } else {
      const passSize = this.frequencyBinCount / 3.

      for (let i = 0; i < this.frequencyBinCount; i++) {
        const val = Math.pow(this.audioBuffer[i] / 196., 3.)

        if (i < passSize)
          bass += val
        else if (i >= passSize && i < passSize * 2)
          mid += val
        else if (i >= passSize * 2)
          high += val
      }

      bass /= passSize
      mid /= passSize
      high /= passSize
    }

    this.bass = this.bass > bass ? this.bass * .96 : bass
    this.mid = this.mid > mid ? this.mid * .96 : mid
    this.high = this.high > high ? this.high * .96 : high

    this.level = (this.bass + this.mid + this.high) / 3.

    this.history += this.level * .01 + .005

    this.frame++
  }

  getBass () {
    return this.bass || 0.
  }

  getMid () {
    return this.mid || 0.
  }

  getHigh () {
    return this.high || 0.
  }

  getLevel () {
    return this.level || 0.
  }

  getHistory () {
    return this.history || 0.
  }
}
