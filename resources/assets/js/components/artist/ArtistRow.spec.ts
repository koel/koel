import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Router from '@/router'
import Component from './ArtistRow.vue'

describe('artistRow.vue', () => {
  const h = createHarness()

  const renderComponent = (overrides: Partial<Artist> = {}) => {
    const artist = h.factory('artist').make({
      id: 'led-zeppelin',
      name: 'Led Zeppelin',
      image: 'https://example.com/lz.jpg',
      favorite: false,
      rating: 0,
      ...overrides,
    })

    return { artist, ...h.render(Component, { props: { artist } }) }
  }

  it('renders the artist name as a link to the detail page', () => {
    renderComponent()

    expect(screen.getByRole('link', { name: 'Led Zeppelin' })).toHaveProperty(
      'href',
      expect.stringContaining('/artists/led-zeppelin'),
    )
  })

  it('emits toggle-favorite when the favorite button is clicked', async () => {
    const { artist, emitted } = renderComponent({ favorite: true })

    await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

    expect(emitted('toggle-favorite')?.[0]).toEqual([artist])
  })

  it('navigates to the artist on double-click', async () => {
    const goMock = h.mock(Router, 'go')
    renderComponent()

    await h.user.dblClick(screen.getByTestId('artist-row'))

    expect(goMock).toHaveBeenCalledWith(expect.stringContaining('/artists/led-zeppelin'))
  })
})
