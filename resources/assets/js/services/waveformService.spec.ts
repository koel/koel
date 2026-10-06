import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { waveformService } from './waveformService'

describe('waveformService', () => {
  const h = createHarness()

  it('fetches the waveform of a song', async () => {
    const song = h.factory('song').make()
    const getMock = h.mock(http, 'get').mockResolvedValue({ waveform: [0.1, 0.5] })

    expect(await waveformService.fetchWaveform(song)).toEqual([0.1, 0.5])
    expect(getMock).toHaveBeenCalledWith(`songs/${song.id}/waveform`)
  })

  it('averages the waveform into the requested number of bars, relative to the loudest', () => {
    const waveform = [...Array(200).fill(0.25), ...Array(200).fill(0.5), ...Array(400).fill(0.02)]

    expect(waveformService.toBarLevels(waveform, 4)).toEqual([0.5, 1, 0.1, 0.1])
  })

  it('stretches a short waveform over more bars', () => {
    expect(waveformService.toBarLevels([0.25, 0.5], 4)).toEqual([0.5, 0.5, 1, 1])
  })

  it('returns no bars for an empty waveform or no room', () => {
    expect(waveformService.toBarLevels([], 10)).toEqual([])
    expect(waveformService.toBarLevels([0.5], 0)).toEqual([])
  })
})
