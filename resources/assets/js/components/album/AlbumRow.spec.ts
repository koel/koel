import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Router from '@/router'
import Component from './AlbumRow.vue'

describe('albumRow.vue', () => {
  const h = createHarness()

  const renderComponent = (overrides: Partial<Album> = {}) => {
    const album = h.factory('album').make({
      id: 'iv',
      name: 'IV',
      artist_id: 'led-zeppelin',
      artist_name: 'Led Zeppelin',
      cover: 'https://example.com/cover.jpg',
      length: 2542,
      year: 1971,
      favorite: false,
      rating: 0,
      ...overrides,
    })

    return { album, ...h.render(Component, { props: { album } }) }
  }

  it('renders the album name and artist link', () => {
    renderComponent()

    screen.getByText('IV')
    expect(screen.getByRole('link', { name: 'Led Zeppelin' })).toHaveProperty(
      'href',
      expect.stringContaining('/artists/led-zeppelin'),
    )
  })

  it('emits toggle-favorite when the favorite button is clicked', async () => {
    const { album, emitted } = renderComponent({ favorite: true })

    await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))

    expect(emitted('toggle-favorite')?.[0]).toEqual([album])
  })

  it('navigates to the album on double-click', async () => {
    const goMock = h.mock(Router, 'go')
    renderComponent()

    await h.user.dblClick(screen.getByTestId('album-row'))

    expect(goMock).toHaveBeenCalledWith(expect.stringContaining('/albums/iv'))
  })
})
