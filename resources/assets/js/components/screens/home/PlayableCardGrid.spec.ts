import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './PlayableCardGrid.vue'

describe('playableCardGrid.vue', () => {
  const h = createHarness()

  it('renders a grid of playable cards', () => {
    const songs = h.factory('song', 4)
    h.render(Component, { props: { playables: songs } })
    expect(screen.getAllByTestId('song-card')).toHaveLength(4)
  })

  it('renders empty grid when no playables', () => {
    h.render(Component, { props: { playables: [] } })
    expect(screen.queryAllByTestId('song-card')).toHaveLength(0)
  })
})
