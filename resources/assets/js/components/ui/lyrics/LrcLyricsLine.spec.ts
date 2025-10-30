import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './LrcLyricsLine.vue'

describe('lrcLyricsLine.vue', () => {
  const h = createHarness()

  const renderComponent = (line: { time: number, text: string }, isActive = false) => {
    return h.render(Component, {
      props: {
        line,
        isActive,
      },
    })
  }

  it('renders lyrics line text', () => {
    renderComponent({ time: 10.5, text: 'Test lyrics line' })
    screen.getByText('Test lyrics line')
  })

  it('does not have active class when not active', () => {
    renderComponent({ time: 10.5, text: 'Test lyrics line' }, false)
    expect(screen.getByText('Test lyrics line').classList.contains('active')).toBe(false)
  })

  it('has active class when active', () => {
    renderComponent({ time: 10.5, text: 'Test lyrics line' }, true)
    expect(screen.getByText('Test lyrics line').classList.contains('active')).toBe(true)
  })
})
