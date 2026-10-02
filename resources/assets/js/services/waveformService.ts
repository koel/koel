import { http } from '@/services/http'

const BAR_WIDTH = 3
const BAR_GAP = 2
const MIN_BAR_HEIGHT = 0.1
const REFLECTION_OPACITY = 0.35

export const waveformService = {
  async fetchWaveform(song: Song) {
    const { waveform } = await http.get<{ waveform: number[] }>(`songs/${song.id}/waveform`)

    return waveform
  },

  renderBarsAsSvg(waveform: number[], barCount: number) {
    const pointsPerBar = Math.max(1, Math.ceil(waveform.length / barCount))
    const barLevels: number[] = []

    for (let i = 0; i < waveform.length; i += pointsPerBar) {
      const group = waveform.slice(i, i + pointsPerBar)
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
