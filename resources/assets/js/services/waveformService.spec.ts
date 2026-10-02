import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { waveformService } from './waveformService'

describe('waveformService', () => {
  const h = createHarness()

  it('fetches the levels of a song', async () => {
    const song = h.factory('song').make()
    const getMock = h.mock(http, 'get').mockResolvedValue({ levels: [0.1, 0.5] })

    expect(await waveformService.fetchLevels(song)).toEqual([0.1, 0.5])
    expect(getMock).toHaveBeenCalledWith(`songs/${song.id}/waveform`)
  })

  it('draws one bar per group of levels above the middle, relative to the loudest bar', () => {
    const levels = [...Array(200).fill(0.25), ...Array(200).fill(0.5), ...Array(400).fill(0.02)]
    const bars = new DOMParser()
      .parseFromString(waveformService.renderBarsAsSvg(levels, 300), 'image/svg+xml')
      .querySelectorAll('rect:not([fill])')

    expect(bars).toHaveLength(267)
    expect(bars[0].getAttribute('height')).toBe('25')
    expect(bars[67].getAttribute('height')).toBe('50')
    expect(bars[266].getAttribute('height')).toBe('5')
    expect(bars[266].getAttribute('y')).toBe('45')
  })

  it('mirrors each bar below the middle as a reflection that fades out', () => {
    const reflections = new DOMParser()
      .parseFromString(waveformService.renderBarsAsSvg([0.25, 0.5], 300), 'image/svg+xml')
      .querySelectorAll('rect[fill]')

    expect(reflections).toHaveLength(2)
    expect(reflections[0].getAttribute('y')).toBe('50')
    expect(reflections[0].getAttribute('height')).toBe('25')
    expect(reflections[1].getAttribute('height')).toBe('50')
    expect(reflections[1].getAttribute('fill')).toBe('url(#reflection)')
  })

  it('draws at most the requested number of bars', () => {
    const bars = new DOMParser()
      .parseFromString(waveformService.renderBarsAsSvg(Array(800).fill(0.5), 65), 'image/svg+xml')
      .querySelectorAll('rect:not([fill])')

    expect(bars).toHaveLength(62)
  })
})
