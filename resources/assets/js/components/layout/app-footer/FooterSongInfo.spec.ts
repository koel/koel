import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import FooterSongInfo from './FooterSongInfo.vue'
import { ref } from 'vue'
import { CurrentSongKey } from '@/symbols'

new class extends UnitTestCase {
  protected test () {
    it('renders with no current song', () => expect(this.render(FooterSongInfo).html()).toMatchSnapshot())

    it('renders with current song', () => {
      const song = factory<Song>('song', {
        title: 'Fahrstuhl zum Mond',
        album_cover: 'https://via.placeholder.com/150',
        playback_state: 'Playing',
        artist_id: 10,
        artist_name: 'Led Zeppelin'
      })

      expect(this.render(FooterSongInfo, {
        global: {
          provide: {
            [CurrentSongKey]: ref(song)
          }
        }
      }).html()).toMatchSnapshot()
    })
  }
}
