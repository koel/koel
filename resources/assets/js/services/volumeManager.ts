import { ref } from 'vue'
import { preferenceStore as preferences } from '@/stores'

const DEFAULT_VOLUME_VALUE = 7

export class VolumeManager {
  private input!: HTMLInputElement
  public volume = ref(0)

  public init (input: HTMLInputElement) {
    this.input = input
    this.set(preferences.volume || DEFAULT_VOLUME_VALUE)
  }

  public set (volume: number, persist = true) {
    if (persist) {
      preferences.volume = volume
    }

    this.volume.value = volume
    this.input.value = String(volume)
  }

  public get () {
    return this.volume.value
  }

  public mute () {
    this.set(0, false)
  }

  public unmute () {
    this.set(preferences.volume || DEFAULT_VOLUME_VALUE)
  }
}

export const volumeManager = new VolumeManager()
