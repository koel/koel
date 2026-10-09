import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

interface FakeChart {
  config: { data: { labels: string[]; datasets: { data: number[] }[] } }
  data: FakeChart['config']['data']
  update: () => void
  destroy: () => void
}

const { createdCharts } = vi.hoisted(() => ({ createdCharts: [] as FakeChart[] }))

vi.mock('chart.js', () => {
  class Chart implements FakeChart {
    static register = vi.fn()
    config: FakeChart['config']
    data: FakeChart['config']['data']
    update = vi.fn()
    destroy = vi.fn()

    constructor(_canvas: HTMLCanvasElement, config: FakeChart['config']) {
      this.config = config
      this.data = config.data
      createdCharts.push(this)
    }
  }

  return { Chart, BarController: {}, BarElement: {}, CategoryScale: {}, LinearScale: {}, Tooltip: {} }
})

import Component from './PlaysBarChart.vue'

describe('playsBarChart.vue', () => {
  const h = createHarness({ beforeEach: () => createdCharts.splice(0) })

  const bars = [
    { key: 'a', label: 'Mon', plays: 4 },
    { key: 'b', label: 'Tue', plays: 1 },
  ]

  it('draws a bar per period with its plays', () => {
    h.render(Component, { props: { title: 'By day of the week', bars } })

    expect(createdCharts[0].config.data.labels).toEqual(['Mon', 'Tue'])
    expect(createdCharts[0].config.data.datasets[0].data).toEqual([4, 1])
  })

  it('redraws when the plays change', async () => {
    const { rerender } = h.render(Component, { props: { title: 'By day of the week', bars } })

    await rerender({ title: 'By day of the week', bars: [{ key: 'c', label: 'Wed', plays: 7 }] })

    expect(createdCharts[0].data.labels).toEqual(['Wed'])
    expect(createdCharts[0].update).toHaveBeenCalled()
  })

  it('cleans up the chart when it goes away', () => {
    const { unmount } = h.render(Component, { props: { title: 'By day of the week', bars } })

    unmount()

    expect(createdCharts[0].destroy).toHaveBeenCalled()
  })
})
