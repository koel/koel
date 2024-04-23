import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SongListItem from './SongListItem.vue'

let row: SongRow

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const song = factory<Song>('song', {
        title: 'Test Song',
        album_name: 'Test Album',
        artist_name: 'Test Artist',
        length: 1000,
        playback_state: 'Playing',
        track: 12,
        album_cover: 'https://example.com/cover.jpg',
        liked: true
      })

      const { html } = await this.renderComponent(song)
      expect(html()).toMatchSnapshot()
    })

    it('emits play event on double click', async () => {
      const { emitted } = this.renderComponent()
      await this.user.dblClick(screen.getByTestId('song-item'))
      expect(emitted().play).toBeTruthy()
    })
  }

  private renderComponent (song?: Song) {
    song = song ?? factory<Song>('song')

    row = {
      song,
      selected: false
    }

    return this.render(SongListItem, {
      props: {
        item: row
      }
    })
  }
}
