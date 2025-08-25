import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { genreStore } from '@/stores/genreStore'
import Component from './GenreListScreen.vue'

describe('genreListScreen', () => {
  const h = createHarness()

  const renderComponent = async (genres?: Genre[]) => {
    genres = genres || h.factory('genre', 5)
    const fetchMock = h.mock(genreStore, 'fetchAll').mockResolvedValue(genres)

    const rendered = h.render(Component, {
      global: {
        stubs: {
          GenreCard: h.stub('genre-card'),
        },
      },
    })

    return {
      genres,
      fetchMock,
      ...rendered,
    }
  }

  it('renders the list of genres', async () => {
    await renderComponent()
    await waitFor(() => expect(screen.queryAllByTestId('genre-card')).toHaveLength(5))
  })

  it('shows a message when the library is empty', async () => {
    commonStore.state.song_length = 0
    const { fetchMock } = await renderComponent()

    await waitFor(() => {
      expect(fetchMock).not.toHaveBeenCalled()
      screen.getByTestId('screen-empty-state')
    })
  })
})
