import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlaysBarChart.vue'

describe('playsBarChart.vue', () => {
  const h = createHarness()

  it('sizes each bar against the busiest one', () => {
    h.render(Component, {
      props: {
        title: 'By hour of the day',
        bars: [
          { key: 'a', label: 'A', plays: 4 },
          { key: 'b', label: 'B', plays: 1 },
          { key: 'c', label: 'C', plays: 0 },
        ],
      },
    })

    const heights = Array.from(screen.getByTestId('plays-bars').children).map(bar => (bar as HTMLElement).style.height)

    expect(heights).toEqual(['100%', '25%', '0%'])
  })
})
