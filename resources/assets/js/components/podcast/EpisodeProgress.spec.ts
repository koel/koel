import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './EpisodeProgress.vue'

describe('EpisodeProgress', () => {
  const h = createHarness()

  it('renders progress bar with correct percentage', () => {
    const episode = h.factory('episode', { length: 200 })
    const { container } = h.render(Component, { props: { episode, position: 50 } })

    const bar = container.querySelector('span')!
    expect(bar.style.width).toBe('25%')
  })

  it('renders 0% for position 0', () => {
    const episode = h.factory('episode', { length: 100 })
    const { container } = h.render(Component, { props: { episode, position: 0 } })

    const bar = container.querySelector('span')!
    expect(bar.style.width).toBe('0%')
  })

  it('renders 100% when fully played', () => {
    const episode = h.factory('episode', { length: 300 })
    const { container } = h.render(Component, { props: { episode, position: 300 } })

    const bar = container.querySelector('span')!
    expect(bar.style.width).toBe('100%')
  })
})
