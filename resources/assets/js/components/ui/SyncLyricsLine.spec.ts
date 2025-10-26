import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SyncLyricsLine.vue'

describe('syncLyricsLine.vue', () => {
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
    const { html } = renderComponent({ time: 10.5, text: 'Test lyrics line' })
    expect(html()).toContain('Test lyrics line')
  })

  it('does not have active class when not active', () => {
    const { container } = renderComponent({ time: 10.5, text: 'Test lyrics line' }, false)
    expect(container.querySelector('.active')).toBeFalsy()
  })

  it('has active class when active', () => {
    const { container } = renderComponent({ time: 10.5, text: 'Test lyrics line' }, true)
    expect(container.querySelector('.active')).toBeTruthy()
  })
})
