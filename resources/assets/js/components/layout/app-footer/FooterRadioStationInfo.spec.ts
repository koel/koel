import { describe, expect, it } from 'vitest'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/symbols'
import Component from './FooterRadioStationInfo.vue'

describe('footerRadioStationInfo.vue', () => {
  const h = createHarness()

  it('renders with current radio', () => {
    const station = h.factory('radio-station', {
      name: 'Classic Rock',
      logo: 'https://via.placeholder.com/150',
      description: 'The best classic rock hits',
      playback_state: 'Playing',
    })

    expect(h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(station),
        },
      },
    }).html()).toMatchSnapshot()
  })
})
