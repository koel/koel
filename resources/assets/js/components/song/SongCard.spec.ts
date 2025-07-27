import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playbackService } from '@/services/playbackService'
import { songStore } from '@/stores/songStore'
import Component from './SongCard.vue'

new class extends UnitTestCase {
  protected test () {
    it('has a thumbnail and a Favorite button', () => {
      this.renderComponent()
      screen.getByTestId('thumbnail')
      screen.getByTestId('favorite-button')
    })

    it('toggles the favorite state when the Favorite button is clicked', async () => {
      const { playable } = this.renderComponent('Stopped', false)
      const toggleFavoriteMock = this.mock(songStore, 'toggleFavorite')

      await this.user.click(screen.getByRole('button', { name: 'Favorite' }))

      expect(toggleFavoriteMock).toHaveBeenCalledWith(playable)
    })

    it('queues and plays on double-click', async () => {
      const playMock = this.mock(playbackService, 'play')
      const { playable } = this.renderComponent()

      await this.user.dblClick(screen.getByRole('article'))

      expect(playMock).toHaveBeenCalledWith(playable)
    })
  }

  private renderComponent (
    playbackState: PlaybackState = 'Stopped',
    mockFavoriteButton = true,
  ) {
    const playable = factory('song', {
      playback_state: playbackState,
      play_count: 10,
      title: 'Foo bar',
      favorite: false,
    })

    const stubs = {
      SongThumbnail: this.stub('thumbnail'),
    }

    if (mockFavoriteButton) {
      stubs.FavoriteButton = this.stub('favorite-button')
    }

    const rendered = this.render(Component, {
      props: {
        playable,
      },
      global: {
        stubs,
      },
    })

    return {
      ...rendered,
      playable,
    }
  }
}
