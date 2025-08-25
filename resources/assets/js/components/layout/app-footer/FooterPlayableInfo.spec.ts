import { describe, expect, it } from 'vitest'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/symbols'
import Component from './FooterPlayableInfo.vue'

describe('footerPlayableInfo.vue', () => {
  const h = createHarness()

  it('renders with no current playable', () => expect(h.render(Component).html()).toMatchSnapshot())

  it('renders with current playable', () => {
    const song = h.factory('song', {
      title: 'Fahrstuhl zum Mond',
      album_cover: 'https://via.placeholder.com/150',
      playback_state: 'Playing',
      artist_id: 'led-zeppelin',
      artist_name: 'Led Zeppelin',
    })

    expect(h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(song),
        },
      },
    }).html()).toMatchSnapshot()
  })
})
