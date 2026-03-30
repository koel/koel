import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlayIcon.vue'

describe('playIcon.vue', () => {
  const h = createHarness()

  it('renders play icon by default', () => {
    h.render(Component)
    expect(screen.getByTestId('icon-play')).toBeTruthy()
    expect(screen.queryByTestId('icon-pause')).toBeNull()
  })

  it('renders pause icon when playing', () => {
    h.render(Component, { props: { playing: true } })
    expect(screen.getByTestId('icon-pause')).toBeTruthy()
    expect(screen.queryByTestId('icon-play')).toBeNull()
  })
})
