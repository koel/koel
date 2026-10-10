import { rgbToHsl } from '@/utils/color'
import type { Rgb } from '@/utils/color'
import { cache } from '@/services/cache'

const SAMPLE_SIZE = 32
const HUE_BUCKETS = 24
const MIN_LIGHTNESS = 0.55
const MAX_BACKGROUND_LIGHTNESS = 0.25
const THEME_BACKGROUND_LIGHTNESS = 0.1
const MAX_THEME_BACKGROUND_SATURATION = 0.35
const MIN_THEME_HIGHLIGHT_LIGHTNESS = 0.55
const MAX_THEME_HIGHLIGHT_LIGHTNESS = 0.65

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

export const darkenToBackground = (rgb: Rgb) => {
  const { hue, saturation, lightness } = rgbToHsl(rgb)

  return `hsl(${Math.round(hue)} ${Math.round(saturation * 100)}% ${Math.round(Math.min(lightness, MAX_BACKGROUND_LIGHTNESS) * 100)}%)`
}

const hsl = (hue: number, saturation: number, lightness: number) =>
  `hsl(${Math.round(hue)} ${Math.round(saturation * 100)}% ${Math.round(lightness * 100)}%)`

export const toThemeColors = (rgb: Rgb) => {
  const { hue, saturation, lightness } = rgbToHsl(rgb)

  return {
    background: hsl(hue, Math.min(saturation, MAX_THEME_BACKGROUND_SATURATION), THEME_BACKGROUND_LIGHTNESS),
    highlight: hsl(
      hue,
      saturation,
      Math.min(Math.max(lightness, MIN_THEME_HIGHLIGHT_LIGHTNESS), MAX_THEME_HIGHLIGHT_LIGHTNESS),
    ),
  }
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
    return pickVividColor(context.getImageData(0, 0, SAMPLE_SIZE, SAMPLE_SIZE).data)
  } catch {
    return null
  }
}

/**
 * The cover's main vivid color, or null when the cover has none or can't be read
 * (e.g. hosted elsewhere without CORS, which keeps its pixels unreadable).
 */
const getCoverRgb = (url: string) => {
  const key = ['cover.color', url]

  if (cache.miss(key)) {
    cache.set(key, extract(url))
  }

  return cache.get<Promise<Rgb | null>>(key)
}

export const getCoverColor = async (url: string) => {
  const rgb = await getCoverRgb(url)

  return rgb ? brightenToVisible(rgb) : null
}

export const getCoverThemeColors = async (url: string) => {
  const rgb = await getCoverRgb(url)

  return rgb ? toThemeColors(rgb) : null
}

export const getCoverBackgroundColor = async (url: string) => {
  const rgb = await getCoverRgb(url)

  return rgb ? darkenToBackground(rgb) : null
}
