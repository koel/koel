import { describe, expect, it } from 'vite-plus/test'
import { splitIntoFrequencyBands } from './frequencyBands'

describe('splitIntoFrequencyBands', () => {
  it('averages bins below 250 Hz as bass, up to 4 kHz as mid, and the rest as high', () => {
    const binWidthHz = 100
    const levels = [...Array(3).fill(0.75), ...Array(37).fill(0.5), ...Array(60).fill(0.25)]

    expect(splitIntoFrequencyBands(levels, binWidthHz)).toEqual({ bass: 0.75, mid: 0.5, high: 0.25 })
  })

  it('reports silence for a band with no bins', () => {
    expect(splitIntoFrequencyBands([0.4], 1000)).toEqual({ bass: 0.4, mid: 0, high: 0 })
  })
})
