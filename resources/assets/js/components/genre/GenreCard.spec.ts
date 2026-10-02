import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './GenreCard.vue'

describe('genreCard.vue', () => {
  const h = createHarness()

  const createGenre = (overrides: Partial<Genre> = {}): Genre => {
    return h.factory('genre').make({
      id: 'foo',
      name: 'Classical',
      song_count: 99,
      ...overrides,
    })
  }

  const renderComponent = (genre?: Genre) => {
    genre = genre || createGenre()

    const render = h.render(Component, {
      props: {
        genre: genre || createGenre(),
      },
    })

    return {
      ...render,
      genre,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())
})
