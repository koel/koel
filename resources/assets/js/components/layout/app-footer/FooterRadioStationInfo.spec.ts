import { describe, expect, it } from 'vite-plus/test'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/config/symbols'
import { radioStationStore } from '@/stores/radioStationStore'
import Component from './FooterRadioStationInfo.vue'

describe('footerRadioStationInfo.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      radioStationStore.nowPlaying.value = null
    },
  })

  it('renders with current radio', () => {
    const station = h.factory('radio-station', {
      name: 'Classic Rock',
      logo: 'https://via.placeholder.com/150',
      description: 'The best classic rock hits',
      playback_state: 'Playing',
    })

    expect(
      h
        .render(Component, {
          global: {
            provide: {
              [<symbol>CurrentStreamableKey]: ref(station),
            },
          },
        })
        .html(),
    ).toMatchSnapshot()
  })

  it('shows now-playing info when available', async () => {
    const station = h.factory('radio-station', {
      name: 'Classic Rock',
      description: 'The best classic rock hits',
      playback_state: 'Playing',
    })

    radioStationStore.nowPlaying.value = 'Led Zeppelin - Stairway to Heaven'

    const { html } = h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(station),
        },
      },
    })

    expect(html()).toContain('Led Zeppelin - Stairway to Heaven')
    expect(html()).not.toContain('The best classic rock hits')
  })

  it('shows description when no now-playing info', () => {
    const station = h.factory('radio-station', {
      name: 'Classic Rock',
      description: 'The best classic rock hits',
      playback_state: 'Playing',
    })

    const { html } = h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(station),
        },
      },
    })

    expect(html()).toContain('The best classic rock hits')
  })
})
