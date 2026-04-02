import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
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

import Component from './PlayableCard.vue'

describe('playableCard.vue', () => {
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

  const renderCard = (overrides: Partial<Song> = {}) => {
    const song = h.factory('song', {
      title: 'Test Song',
      artist_name: 'Test Artist',
      length: 195,
      playback_state: 'Stopped',
      ...overrides,
    })

    return h.render(Component, { props: { playable: song } })
  }

  it('renders song info', () => {
    renderCard()
    screen.getByText('Test Song')
    screen.getByText('Test Artist')
    screen.getByText('03:15')
  })

  it('highlights the playing song', () => {
    renderCard({ playback_state: 'Playing' })
    expect(screen.getByTestId('song-card').classList).toContain('playing')
  })

  it('does not highlight a stopped song', () => {
    renderCard({ playback_state: 'Stopped' })
    expect(screen.getByTestId('song-card').classList).not.toContain('playing')
  })

  it('is draggable', () => {
    renderCard()
    expect(screen.getByTestId('song-card').getAttribute('draggable')).toBe('true')
  })

  it('shows offline mark for cached songs', () => {
    isCachedMock.mockReturnValue(true)
    renderCard()
    screen.getByTitle('Available offline')
  })

  it('does not show offline mark for non-cached songs', () => {
    renderCard()
    expect(screen.queryByTitle('Available offline')).toBeNull()
  })

  it('shows spinner when caching offline', () => {
    isCachingMock.mockReturnValue(true)
    renderCard()
    screen.getByTitle('Caching for offline playback')
  })

  it('shows spinner instead of offline mark when caching', () => {
    isCachingMock.mockReturnValue(true)
    isCachedMock.mockReturnValue(true)
    renderCard()
    screen.getByTitle('Caching for offline playback')
    expect(screen.queryByTitle('Available offline')).toBeNull()
  })

  it('shows error icon when caching fails', () => {
    hasCachingErrorMock.mockReturnValue(true)
    getCachingErrorMock.mockReturnValue('Network error')
    renderCard()
    screen.getByTitle('Error: Network error')
  })
})
