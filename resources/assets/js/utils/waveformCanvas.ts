export const WAVEFORM_POINT_SPACING = 6

export const countWaveformPoints = (width: number) => Math.max(2, Math.floor(width / WAVEFORM_POINT_SPACING) + 1)

interface LevelShaping {
  levels: number[]
  growth: (bar: number) => number
}

/**
 * Scales every point of the static waveform by its song-change growth.
 */
export const shapeLevels = ({ levels, growth }: LevelShaping) => levels.map((level, point) => level * growth(point))

interface Point {
  x: number
  y: number
}

/**
 * Finds the height of the smoothed line at `x`. Between the midpoints of neighboring points the line is a
 * quadratic curve with the point itself as the control; with evenly spaced points, x moves linearly along it.
 */
export const lineYAt = (points: Point[], x: number) => {
  if (points.length < 2) {
    return points[0]?.y ?? 0
  }

  const step = points[1].x - points[0].x
  const index = Math.min(points.length - 2, Math.max(1, Math.round(x / step)))
  const control = points[index]
  const previous = points[index - 1]
  const next = points[index + 1]
  const start = index === 1 ? previous : { x: (previous.x + control.x) / 2, y: (previous.y + control.y) / 2 }
  const end = index === points.length - 2 ? next : { x: (control.x + next.x) / 2, y: (control.y + next.y) / 2 }
  const t = Math.min(1, Math.max(0, (x - start.x) / (end.x - start.x)))

  return (1 - t) * (1 - t) * start.y + 2 * (1 - t) * t * control.y + t * t * end.y
}

const TAIL_LENGTH = 160
export const GLOW_ROOM = 16

/**
 * Canvas normalizes any CSS color it accepts to `#rrggbb` when it's opaque, which lets us build
 * see-through stops of the same color for the tail gradient.
 */
const toRgb = (context: CanvasRenderingContext2D, color: string) => {
  context.fillStyle = color
  const hex = context.fillStyle.replace('#', '')

  return [0, 2, 4].map(offset => Number.parseInt(hex.slice(offset, offset + 2), 16) || 0)
}

interface WaveformFrame {
  levels: number[]
  progress: number
  playedOpacity: number
  colors: { accent: string; line: string }
}

/**
 * Draws a smooth line through the waveform's points, rising from the canvas's bottom edge, with a glowing
 * dot at `progress` trailing a fading tail of the accent color.
 */
export const drawWaveform = (canvas: HTMLCanvasElement, frame: WaveformFrame) => {
  const context = canvas.getContext('2d')

  if (!context) {
    return
  }

  const pixelRatio = window.devicePixelRatio || 1
  const width = canvas.clientWidth
  const height = canvas.clientHeight

  if (canvas.width !== Math.round(width * pixelRatio) || canvas.height !== Math.round(height * pixelRatio)) {
    canvas.width = Math.round(width * pixelRatio)
    canvas.height = Math.round(height * pixelRatio)
  }

  context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0)
  context.clearRect(0, 0, width, height)

  const { levels, progress, playedOpacity, colors } = frame

  if (levels.length < 2) {
    return
  }

  const maxLevelHeight = height - GLOW_ROOM
  const step = width / (levels.length - 1)
  const points = levels.map((level, index) => ({ x: index * step, y: height - level * maxLevelHeight }))

  const traceCurve = () => {
    context.beginPath()
    context.moveTo(points[0].x, points[0].y)

    for (let index = 1; index < points.length - 1; index++) {
      const next = points[index + 1]
      context.quadraticCurveTo(
        points[index].x,
        points[index].y,
        (points[index].x + next.x) / 2,
        (points[index].y + next.y) / 2,
      )
    }

    context.lineTo(points[points.length - 1].x, points[points.length - 1].y)
  }

  const strokeLine = (color: string | CanvasGradient, opacity: number) => {
    context.strokeStyle = color
    context.globalAlpha = opacity
    context.lineWidth = 1.5
    context.lineJoin = 'round'
    context.lineCap = 'round'
    traceCurve()
    context.stroke()
  }

  traceCurve()
  context.lineTo(width, height)
  context.lineTo(0, height)
  context.closePath()
  const [fillRed, fillGreen, fillBlue] = toRgb(context, colors.line)
  const fill = context.createLinearGradient(0, GLOW_ROOM, 0, GLOW_ROOM + (height - GLOW_ROOM) * 0.6)
  fill.addColorStop(0, `rgba(${fillRed}, ${fillGreen}, ${fillBlue}, 0.1)`)
  fill.addColorStop(1, `rgba(${fillRed}, ${fillGreen}, ${fillBlue}, 0)`)
  context.fillStyle = fill
  context.globalAlpha = 1
  context.fill()

  strokeLine(colors.line, 0.12)

  if (progress > 0) {
    const x = progress * width
    const tailStart = Math.max(0, x - TAIL_LENGTH)
    const [red, green, blue] = toRgb(context, colors.accent)
    const tail = context.createLinearGradient(tailStart, 0, x, 0)
    tail.addColorStop(0, `rgba(${red}, ${green}, ${blue}, 0)`)
    tail.addColorStop(1, `rgba(${red}, ${green}, ${blue}, 1)`)

    context.save()
    context.beginPath()
    context.rect(tailStart, 0, x - tailStart, height)
    context.clip()
    strokeLine(tail, playedOpacity)
    context.restore()

    const y = lineYAt(points, x)
    const dotOpacity = Math.min(1, playedOpacity / 0.8)

    const halo = context.createRadialGradient(x, y, 0, x, y, 14)
    halo.addColorStop(0, `rgba(${red}, ${green}, ${blue}, 0.45)`)
    halo.addColorStop(1, `rgba(${red}, ${green}, ${blue}, 0)`)
    context.globalAlpha = dotOpacity
    context.fillStyle = halo
    context.beginPath()
    context.arc(x, y, 14, 0, Math.PI * 2)
    context.fill()

    context.fillStyle = colors.accent
    context.shadowColor = colors.accent
    context.shadowBlur = 22
    context.beginPath()
    context.arc(x, y, 4, 0, Math.PI * 2)
    context.fill()
    context.shadowBlur = 0
    context.fillStyle = '#ffffff'
    context.globalAlpha = dotOpacity * 0.9
    context.beginPath()
    context.arc(x, y, 1.8, 0, Math.PI * 2)
    context.fill()
  }

  context.globalAlpha = 1
}
