import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { playableStore } from '@/stores/playableStore'
import { PlayableListConfigKey } from '@/config/symbols'
import { createHarness } from '@/__tests__/TestHarness'

const isCachedMock = vi.fn().mockReturnValue(false)
const isCachingMock = vi.fn().mockReturnValue(false)
const hasCachingErrorMock = vi.fn().mockReturnValue(false)
const getCachingErrorMock = vi.fn().mockReturnValue(undefined)

vi.mock('@/composables/useOfflinePlayback', () => ({
  useOfflinePlayback: () => ({
    isCached: isCachedMock,
    isCaching: isCachingMock,
    hasCachingError: hasCachingErrorMock,
    getCachingError: getCachingErrorMock,
  }),
}))

import Component from './PlayableListItem.vue'

describe('playableListItem.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      isCachedMock.mockClear()
      isCachedMock.mockReturnValue(false)
      isCachingMock.mockClear()
      isCachingMock.mockReturnValue(false)
      hasCachingErrorMock.mockClear()
      hasCachingErrorMock.mockReturnValue(false)
      getCachingErrorMock.mockClear()
      getCachingErrorMock.mockReturnValue(undefined)
    },
  })

  const renderComponent = (playable?: Playable, showDisc = false) => {
    playable = playable ?? h.factory('song', { favorite: false })

    const row = {
      playable,
      selected: false,
    }

    const rendered = h.render(Component, {
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

  it('renders song details', () => {
    const song = h.factory('song', {
      title: 'Test Song',
      album_name: 'Test Album',
      artist_name: 'Test Artist',
      length: 1000,
      playback_state: 'Playing',
      track: 12,
      album_cover: 'https://example.com/cover.jpg',
      favorite: true,
    })

    renderComponent(song)

    screen.getByText('Test Song')
    screen.getByText('Test Artist')
    screen.getByText('Test Album')
    screen.getByRole('button', { name: 'Undo Favorite' })
  })

  it('emits play event on double click', async () => {
    const { emitted } = renderComponent()
    await h.user.dblClick(screen.getByTestId('song-item'))
    expect(emitted().play).toBeTruthy()
  })

  it('renders disc info when showDisc is true', async () => {
    const song = h.factory('song', {
      disc: 2,
      title: 'Test Song',
    })

    const showDisc = true
    const { getByText } = renderComponent(song, showDisc)
    expect(getByText('Disc 2')).toBeTruthy()
  })

  it('shows collaboration info when collaborative', () => {
    const song = h.factory('song', {
      collaboration: {
        user: { name: 'Alice', avatar: 'https://example.com/alice.jpg' },
        added_at: '2025-01-01',
        fmt_added_at: 'Jan 1, 2025',
      },
    })

    const { getByText } = h.render(Component, {
      props: {
        item: { playable: song, selected: false },
      },
      global: {
        provide: {
          [<symbol>PlayableListConfigKey]: [{ collaborative: true }],
        },
      },
    })

    expect(getByText('Jan 1, 2025')).toBeTruthy()
  })

  it('does not show collaboration info when not collaborative', () => {
    const song = h.factory('song', {
      collaboration: {
        user: { name: 'Alice', avatar: 'https://example.com/alice.jpg' },
        added_at: '2025-01-01',
        fmt_added_at: 'Jan 1, 2025',
      },
    })

    const { queryByText } = h.render(Component, {
      props: {
        item: { playable: song, selected: false },
      },
    })

    expect(queryByText('Jan 1, 2025')).toBeNull()
  })

  it('toggles favorite state when the Favorite button is clicked', async () => {
    const toggleFavoriteMock = h.mock(playableStore, 'toggleFavorite')
    const { row } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Favorite' }))

    expect(toggleFavoriteMock).toHaveBeenCalledWith(row.playable)
  })

  it('shows spinner when caching offline', () => {
    isCachingMock.mockReturnValue(true)
    renderComponent()
    screen.getByTitle('Caching for offline playback')
  })

  it('shows spinner instead of offline mark when caching', () => {
    isCachingMock.mockReturnValue(true)
    isCachedMock.mockReturnValue(true)
    renderComponent()
    screen.getByTitle('Caching for offline playback')
    expect(screen.queryByTitle('Available offline')).toBeNull()
  })

  it('shows error icon when caching fails', () => {
    hasCachingErrorMock.mockReturnValue(true)
    getCachingErrorMock.mockReturnValue('Network error')
    renderComponent()
    screen.getByTitle('Error: Network error')
  })
})
