import { cache } from '@/services/cache'
import { http } from '@/services/http'

const BAR_WIDTH = 3
const BAR_GAP = 2
const MIN_BAR_HEIGHT = 0.1
const REFLECTION_OPACITY = 0.35

export const waveformService = {
  async fetchLevels(song: Song) {
    const cacheKey = ['song.waveform', song.id]

    if (cache.has(cacheKey)) {
      return cache.get<number[]>(cacheKey)
    }

    const { levels } = await http.get<{ levels: number[] }>(`songs/${song.id}/waveform`)
    cache.set(cacheKey, levels)

    return levels
  },

  renderBarsAsSvg(levels: number[], barCount: number) {
    const levelsPerBar = Math.max(1, Math.ceil(levels.length / barCount))
    const barLevels: number[] = []

    for (let i = 0; i < levels.length; i += levelsPerBar) {
      const group = levels.slice(i, i + levelsPerBar)
      barLevels.push(group.reduce((sum, level) => sum + level, 0) / group.length)
    }

    const loudestBarLevel = Math.max(...barLevels) || 1

    const barHeights = barLevels.map(level => Math.max(MIN_BAR_HEIGHT, level / loudestBarLevel))

    const width = barHeights.length * (BAR_WIDTH + BAR_GAP)

    const rects = barHeights.map((barHeight, i) => {
      const x = i * (BAR_WIDTH + BAR_GAP)
      const halfHeight = Math.round(barHeight * 500) / 10

      return (
        `<rect x="${x}" y="${50 - halfHeight}" width="${BAR_WIDTH}" height="${halfHeight}"/>` +
        `<rect x="${x}" y="50" width="${BAR_WIDTH}" height="${halfHeight}" fill="url(#reflection)"/>`
      )
    })

    const reflectionGradient =
      '<defs><linearGradient id="reflection" x2="0" y2="1">' +
      `<stop offset="0" stop-opacity="${REFLECTION_OPACITY}"/><stop offset="1" stop-opacity="0"/>` +
      '</linearGradient></defs>'

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${width} 100" preserveAspectRatio="none">${reflectionGradient}${rects.join('')}</svg>`
  },
}
