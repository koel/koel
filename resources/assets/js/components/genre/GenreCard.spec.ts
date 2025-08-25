import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './GenreCard.vue'

describe('genreCard.vue', () => {
  const h = createHarness()

  const createGenre = (overrides: Partial<Genre> = {}): Genre => {
    return h.factory('genre', {
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

  it('requests context menu', async () => {
    const { genre } = renderComponent()
    const emitMock = h.mock(eventBus, 'emit')
    await h.trigger(screen.getByRole('listitem'), 'contextMenu')

    expect(emitMock).toHaveBeenCalledWith('GENRE_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), genre)
  })
})
