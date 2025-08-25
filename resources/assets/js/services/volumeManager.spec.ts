import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { volumeManager } from '@/services/volumeManager'

describe('volumeManager', () => {
  let input!: HTMLInputElement

  createHarness({
    beforeEach: () => {
      input = document.createElement('input')
      volumeManager.init(input, 5)
    },
  })

  it('gets volume', () => expect(volumeManager.get()).toEqual(5))

  it('sets volume', () => {
    volumeManager.set(4.2)
    expect(volumeManager.volume.value).toEqual(4.2)
    expect(input.value).toEqual('4.2')
  })

  it('mutes', () => {
    volumeManager.mute()
    expect(volumeManager.volume.value).toEqual(0)
    expect(input.value).toEqual('0')
  })

  it('unmutes', () => {
    volumeManager.set(7)
    volumeManager.mute()
    volumeManager.unmute()
    expect(volumeManager.volume.value).toEqual(7)
    expect(input.value).toEqual('7')
  })
})
