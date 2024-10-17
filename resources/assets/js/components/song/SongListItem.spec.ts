import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SongListItem from './SongListItem.vue'

let row: PlayableRow

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const song = factory('song', {
        title: 'Test Song',
        album_name: 'Test Album',
        artist_name: 'Test Artist',
        length: 1000,
        playback_state: 'Playing',
        track: 12,
        album_cover: 'https://example.com/cover.jpg',
        liked: true,
      })

      const { html } = this.renderComponent(song)
      expect(html()).toMatchSnapshot()
    })

    it('emits play event on double click', async () => {
      const { emitted } = this.renderComponent()
      await this.user.dblClick(screen.getByTestId('song-item'))
      expect(emitted().play).toBeTruthy()
    })

    it('renders disc info when showDisc is true', async () => {
      const song = factory('song', {
        disc: 2,
        title: 'Test Song',
      })

      const showDisc = true
      const { getByText } = this.renderComponent(song, showDisc)
      expect(getByText('Disc 2')).toBeTruthy()
    })
  }

  private renderComponent (playable?: Playable, showDisc = false) {
    playable = playable ?? factory('song')

    row = {
      playable,
      selected: false,
    }

    return this.render(SongListItem, {
      props: {
        item: row,
        showDisc,
      },
    })
  }
}
