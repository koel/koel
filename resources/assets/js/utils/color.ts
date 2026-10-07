export type Rgb = [red: number, green: number, blue: number]

/**
 * Returns the red, green and blue channels (0-255) of any CSS color, or null when it can't be parsed.
 * The probe-element trick lets the browser parse any CSS color syntax
 * (hex, rgb/rgba, hsl/hsla, oklch, named colors) and normalize it to rgb().
 */
export const cssColorToRgb = (cssColor: string): Rgb | null => {
  const probe = document.createElement('div')
  probe.style.color = cssColor
  document.body.appendChild(probe)
  const computed = window.getComputedStyle(probe).color
  document.body.removeChild(probe)

  const channels = computed.match(/\d+(?:\.\d+)?/g)

  if (!channels || channels.length < 3) {
    return null
  }

  const [red, green, blue] = channels.map(Number)

  return [red, green, blue]
}

/**
 * Returns true when the given CSS color is perceptually dark.
 *
 * The threshold matches what the `color` npm package's `isDark()` used:
 * a YIQ-weighted brightness < 128 on the 0-255 scale (W3C WCAG 1.0).
 */
export const isDarkColor = (cssColor: string) => {
  const rgb = cssColorToRgb(cssColor)

  if (!rgb) {
    return true
  }

  const [red, green, blue] = rgb
  return (red * 299 + green * 587 + blue * 114) / 1000 < 128
}

export const rgbToHsl = ([red, green, blue]: Rgb) => {
  const r = red / 255
  const g = green / 255
  const b = blue / 255
  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const lightness = (max + min) / 2
  const delta = max - min

  if (delta === 0) {
    return { hue: 0, saturation: 0, lightness }
  }

  const saturation = delta / (1 - Math.abs(2 * lightness - 1))
  let hue: number

  if (max === r) {
    hue = ((g - b) / delta) % 6
  } else if (max === g) {
    hue = (b - r) / delta + 2
  } else {
    hue = (r - g) / delta + 4
  }

  return { hue: (hue * 60 + 360) % 360, saturation, lightness }
}
