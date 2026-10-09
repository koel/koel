import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('vue-chartjs', () => ({
  Bar: defineComponent({
    props: ['data', 'options'],
    template: `<div data-testid="bar-chart" :data-labels="JSON.stringify(data.labels)" :data-plays="JSON.stringify(data.datasets[0].data)" />`,
  }),
}))

import Component from './PlaysBarChart.vue'

describe('playsBarChart.vue', () => {
  const h = createHarness()

  it('charts a bar per period with its plays', () => {
    h.render(Component, {
      props: {
        title: 'By day of the week',
        bars: [
          { key: 'a', label: 'Mon', plays: 4 },
          { key: 'b', label: 'Tue', plays: 1 },
        ],
      },
    })

    const chart = screen.getByTestId('bar-chart')

    expect(JSON.parse(chart.dataset.labels!)).toEqual(['Mon', 'Tue'])
    expect(JSON.parse(chart.dataset.plays!)).toEqual([4, 1])
  })
})
