import { ref } from 'vue'

export class VolumeManager {
  public volume = ref(0)
  private input!: HTMLInputElement
  private originalVolume = 0

  public init (input: HTMLInputElement, initialVolume: number) {
    this.input = input
    this.originalVolume = initialVolume
    this.set(initialVolume)
  }

  public get () {
    return this.volume.value
  }

  public set (volume: number) {
    this.volume.value = volume
    this.input.value = String(volume)
  }

  public mute () {
    this.originalVolume = this.get()
    this.set(0)
  }

  public unmute () {
    this.set(this.originalVolume)
  }

  public toggleMute () {
    if (this.get() === 0) {
      this.unmute()
    } else {
      this.mute()
    }
  }

  public increase (amount = 1) {
    this.set(Math.min(10, this.get() + amount))
  }

  public decrease (amount = 1) {
    this.set(Math.max(this.get() - amount, 0))
  }
}

export const volumeManager = new VolumeManager()
