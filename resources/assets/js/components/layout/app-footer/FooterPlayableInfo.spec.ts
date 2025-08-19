import { expect, it } from 'vitest'
import { ref } from 'vue'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentStreamableKey } from '@/symbols'
import Component from './FooterPlayableInfo.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders with no current playable', () => expect(this.render(Component).html()).toMatchSnapshot())

    it('renders with current playable', () => {
      const song = factory('song', {
        title: 'Fahrstuhl zum Mond',
        album_cover: 'https://via.placeholder.com/150',
        playback_state: 'Playing',
        artist_id: 'led-zeppelin',
        artist_name: 'Led Zeppelin',
      })

      expect(this.render(Component, {
        global: {
          provide: {
            [<symbol>CurrentStreamableKey]: ref(song),
          },
        },
      }).html()).toMatchSnapshot()
    })
  }
}
