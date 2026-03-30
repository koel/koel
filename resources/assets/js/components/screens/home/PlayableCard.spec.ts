import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlayableCard.vue'

describe('playableCard.vue', () => {
  const h = createHarness()

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
})
