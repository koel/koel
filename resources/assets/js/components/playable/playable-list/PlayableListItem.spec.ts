import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { playableStore } from '@/stores/playableStore'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './PlayableListItem.vue'

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
        favorite: true,
      })

      expect(this.renderComponent(song).html()).toMatchSnapshot()
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

    it('toggles favorite state when the Favorite button is clicked', async () => {
      const toggleFavoriteMock = this.mock(playableStore, 'toggleFavorite')
      const { row } = this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Favorite' }))

      expect(toggleFavoriteMock).toHaveBeenCalledWith(row.playable)
    })
  }

  private renderComponent (playable?: Playable, showDisc = false) {
    playable = playable ?? factory('song', { favorite: false })

    const row = {
      playable,
      selected: false,
    }

    const rendered = this.render(Component, {
      props: {
        item: row,
        showDisc,
      },
    })

    return {
      ...rendered,
      row,
    }
  }
}
