import type { Mock } from 'vitest'
import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import GenreContextMenu from '@/components/genre/GenreContextMenu.vue'
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
    vi.mock('@/composables/useContextMenu')
    const { openContextMenu } = useContextMenu()
    const { genre } = renderComponent()

    await h.trigger(screen.getByRole('listitem'), 'contextMenu')
    await assertOpenContextMenu(openContextMenu as Mock, GenreContextMenu, { genre })
  })
})
