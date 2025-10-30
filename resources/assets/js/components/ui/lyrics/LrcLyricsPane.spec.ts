import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './LrcLyricsPane.vue'

describe('lrcLyricsPane.vue', () => {
  const h = createHarness()

  const renderComponent = (lyrics?: Array<{ time: number, text: string }>) => {
    lyrics = lyrics ?? [
      { time: 10.5, text: 'First line' },
      { time: 15.2, text: 'Second line' },
      { time: 20.0, text: 'Third line' },
    ]

    return h.render(Component, {
      props: {
        lyrics,
        fontSize: '1rem',
      },
      global: {
        stubs: {
          LrcLyricsLine: h.stub('lyrics-line'),
        },
      },
    })
  }

  it('renders all lyrics lines', () => {
    renderComponent()
    expect(screen.queryAllByTestId('lyrics-line')).toHaveLength(3)
  })

  it('renders empty when no lyrics provided', () => {
    renderComponent([])
    expect(screen.queryAllByTestId('lyrics-line')).toHaveLength(0)
  })
})
