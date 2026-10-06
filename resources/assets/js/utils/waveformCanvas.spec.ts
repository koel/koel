import { describe, expect, it } from 'vite-plus/test'
import { countWaveformPoints, lineYAt, shapeLevels } from './waveformCanvas'

describe('waveformCanvas', () => {
  const steady = (count: number, level = 0.5) => Array(count).fill(level)

  const shape = (overrides: Partial<Parameters<typeof shapeLevels>[0]>) =>
    shapeLevels({
      levels: steady(100),
      growth: () => 1,
      ...overrides,
    })

  it('spaces the points evenly across the width', () => {
    expect(countWaveformPoints(594)).toBe(100)
    expect(countWaveformPoints(599)).toBe(100)
    expect(countWaveformPoints(1)).toBe(2)
  })

  it('finds the height of the smoothed line between points', () => {
    const flat = [0, 10, 20, 30].map(x => ({ x, y: 5 }))
    expect(lineYAt(flat, 15)).toBe(5)

    const peak = [0, 10, 20, 30, 40].map(x => ({ x, y: x === 20 ? 0 : 10 }))
    expect(lineYAt(peak, 20)).toBe(2.5)
    expect(lineYAt(peak, 15)).toBe(5)
    expect(lineYAt(peak, 0)).toBe(10)
  })

  it('scales each point by its growth during a song change', () => {
    const levels = shape({ growth: bar => (bar < 50 ? 0 : 1) })

    expect(levels[10]).toBe(0)
    expect(levels[80]).toBe(0.5)
  })
})
