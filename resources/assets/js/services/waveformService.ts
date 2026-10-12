import { cache } from '@/services/cache'
import { http } from '@/services/http'

const MIN_BAR_LEVEL = 0.1

export const waveformService = {
  async fetchWaveform(song: Song) {
    return await cache.remember(['song.waveform', song.id], async () => {
      const { waveform } = await http.get<{ waveform: number[] }>(`songs/${song.id}/waveform`)

      return waveform
    })
  },

  /**
   * Squeezes the waveform into exactly `barCount` bars, each the average of its slice of points,
   * scaled so the loudest bar is 1.
   */
  toBarLevels(waveform: number[], barCount: number) {
    if (!waveform.length || barCount < 1) {
      return []
    }

    const averages = Array.from({ length: barCount }, (_, bar) => {
      const start = Math.floor((bar * waveform.length) / barCount)
      const end = Math.max(start + 1, Math.floor(((bar + 1) * waveform.length) / barCount))
      const slice = waveform.slice(start, end)

      return slice.reduce((sum, level) => sum + level, 0) / slice.length
    })

    const loudest = Math.max(...averages) || 1

    return averages.map(level => Math.max(MIN_BAR_LEVEL, level / loudest))
  },
}
