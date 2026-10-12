const BASS_CEILING_HZ = 250
const MID_CEILING_HZ = 4000

const averageOf = (values: number[]) => (values.length ? values.reduce((sum, value) => sum + value, 0) / values.length : 0)

export const splitIntoFrequencyBands = (levels: number[], binWidthHz: number) => {
  const bass: number[] = []
  const mid: number[] = []
  const high: number[] = []

  levels.forEach((level, i) => {
    const binFrequency = i * binWidthHz

    if (binFrequency < BASS_CEILING_HZ) {
      bass.push(level)
    } else if (binFrequency < MID_CEILING_HZ) {
      mid.push(level)
    } else {
      high.push(level)
    }
  })

  return {
    bass: averageOf(bass),
    mid: averageOf(mid),
    high: averageOf(high),
  }
}
