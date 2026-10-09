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

import Component from './ListeningBarChart.vue'

describe('listeningBarChart.vue', () => {
  const h = createHarness()

  it('charts a bar per period with its rounded value', () => {
    h.render(Component, {
      props: {
        title: 'By day of the week',
        measure: 'minutes',
        bars: [
          { key: 'a', label: 'Mon', value: 4.4 },
          { key: 'b', label: 'Tue', value: 0.6 },
        ],
      },
    })

    const chart = screen.getByTestId('bar-chart')

    expect(JSON.parse(chart.dataset.labels!)).toEqual(['Mon', 'Tue'])
    expect(JSON.parse(chart.dataset.plays!)).toEqual([4, 1])
  })
})
