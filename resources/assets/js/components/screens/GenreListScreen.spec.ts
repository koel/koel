import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { commonStore } from '@/stores/commonStore'
import { genreStore } from '@/stores/genreStore'
import Component from './GenreListScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders the list of genres', async () => {
      await this.renderComponent()
      await waitFor(() => expect(screen.queryAllByTestId('genre-card')).toHaveLength(5))
    })

    it('shows a message when the library is empty', async () => {
      commonStore.state.song_length = 0
      const { fetchMock } = await this.renderComponent()

      await waitFor(() => {
        expect(fetchMock).not.toHaveBeenCalled()
        screen.getByTestId('screen-empty-state')
      })
    })
  }

  private async renderComponent (genres?: Genre[]) {
    genres = genres || factory('genre', 5)
    const fetchMock = this.mock(genreStore, 'fetchAll').mockResolvedValue(genres)

    const rendered = this.render(Component, {
      global: {
        stubs: {
          GenreCard: this.stub('genre-card'),
        },
      },
    })

    return {
      genres,
      fetchMock,
      ...rendered,
    }
  }
}
