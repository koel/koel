import { rgbToHsl } from '@/utils/color'
import type { Rgb } from '@/utils/color'

const SAMPLE_SIZE = 32
const HUE_BUCKETS = 24
const MIN_LIGHTNESS = 0.55

const cache = new Map<string, Promise<string | null>>()

const loadImage = (url: string) =>
  new Promise<HTMLImageElement>((resolve, reject) => {
    const image = new Image()
    image.crossOrigin = 'anonymous'
    image.onload = () => resolve(image)
    image.onerror = reject
    image.src = url
  })

/**
 * Picks the cover's most prominent vivid color: pixels are grouped by hue, weighted by how saturated
 * and bright they are, and the heaviest group's average wins. Grays, near-blacks and near-whites are
 * ignored, so a mostly dark or monochrome cover yields null.
 */
export const pickVividColor = (pixels: Uint8ClampedArray) => {
  const buckets = Array.from({ length: HUE_BUCKETS }, () => ({ weight: 0, red: 0, green: 0, blue: 0 }))

  for (let offset = 0; offset < pixels.length; offset += 4) {
    const [red, green, blue, alpha] = pixels.subarray(offset, offset + 4)

    if (alpha < 128) {
      continue
    }

    const { hue, saturation, lightness } = rgbToHsl([red, green, blue])

    if (saturation < 0.25 || lightness < 0.15 || lightness > 0.92) {
      continue
    }

    const bucket = buckets[Math.floor(hue / (360 / HUE_BUCKETS)) % HUE_BUCKETS]
    const weight = saturation * (1 - Math.abs(2 * lightness - 1))

    bucket.weight += weight
    bucket.red += red * weight
    bucket.green += green * weight
    bucket.blue += blue * weight
  }

  const heaviest = buckets.reduce((best, bucket) => (bucket.weight > best.weight ? bucket : best))

  if (heaviest.weight === 0) {
    return null
  }

  const { red, green, blue, weight } = heaviest
  const color: Rgb = [red / weight, green / weight, blue / weight]

  return color
}

export const brightenToVisible = (rgb: Rgb) => {
  const { hue, saturation, lightness } = rgbToHsl(rgb)

  return `hsl(${Math.round(hue)} ${Math.round(saturation * 100)}% ${Math.round(Math.max(lightness, MIN_LIGHTNESS) * 100)}%)`
}

const extract = async (url: string) => {
  try {
    const image = await loadImage(url)
    const canvas = document.createElement('canvas')
    canvas.width = SAMPLE_SIZE
    canvas.height = SAMPLE_SIZE
    const context = canvas.getContext('2d', { willReadFrequently: true })

    if (!context) {
      return null
    }

    context.drawImage(image, 0, 0, SAMPLE_SIZE, SAMPLE_SIZE)
    const color = pickVividColor(context.getImageData(0, 0, SAMPLE_SIZE, SAMPLE_SIZE).data)

    return color ? brightenToVisible(color) : null
  } catch {
    return null
  }
}

/**
 * The cover's main vivid color as a CSS color, or null when the cover has none or can't be read
 * (e.g. hosted elsewhere without CORS, which keeps its pixels unreadable).
 */
export const getCoverColor = (url: string) => {
  if (!cache.has(url)) {
    cache.set(url, extract(url))
  }

  return cache.get(url)!
}
