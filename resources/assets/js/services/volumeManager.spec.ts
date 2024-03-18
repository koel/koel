import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { preferenceStore } from '@/stores'
import { volumeManager, http } from '@/services'

let input: HTMLInputElement

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      input = document.createElement('input')
      volumeManager.init(input, 5)
    })
  }

  protected test () {
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
  }
}
