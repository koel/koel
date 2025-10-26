import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SyncLyricsPane.vue'
import SyncLyricsLine from '@/components/ui/SyncLyricsLine.vue'

describe('syncLyricsPane.vue', () => {
  const h = createHarness()

  const renderComponent = (parsedLyrics?: Array<{ time: number, text: string }>) => {
    const lyrics = parsedLyrics !== undefined
      ? parsedLyrics
      : [
          { time: 10.5, text: 'First line' },
          { time: 15.2, text: 'Second line' },
          { time: 20.0, text: 'Third line' },
        ]

    return h.render(Component, {
      props: {
        parsedLyrics: lyrics,
        fontSize: '1rem',
      },
      global: {
        stubs: {
          SyncLyricsLine,
        },
      },
    })
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('renders all lyrics lines', () => {
    const lyrics = [
      { time: 10.5, text: 'First line' },
      { time: 15.2, text: 'Second line' },
      { time: 20.0, text: 'Third line' },
    ]
    const { container } = renderComponent(lyrics)

    const lines = container.querySelectorAll('.lyrics-line')
    expect(lines.length).toBe(3)
  })

  it('renders empty when no lyrics provided', () => {
    const { container } = renderComponent([])
    const lines = container.querySelectorAll('.lyrics-line')
    expect(lines.length).toBe(0)
  })
})
