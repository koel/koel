import { expect, it } from 'vitest'
import { ref } from 'vue'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentStreamableKey } from '@/symbols'
import Component from './FooterRadioStationInfo.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders with current radio', () => {
      const station = factory('radio-station', {
        name: 'Classic Rock',
        logo: 'https://via.placeholder.com/150',
        description: 'The best classic rock hits',
        playback_state: 'Playing',
      })

      expect(this.render(Component, {
        global: {
          provide: {
            [<symbol>CurrentStreamableKey]: ref(station),
          },
        },
      }).html()).toMatchSnapshot()
    })
  }
}
