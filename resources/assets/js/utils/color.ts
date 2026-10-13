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

const screenLuminance = ([red, green, blue]: Rgb) => {
  const luminance = 0.2126729 * (red / 255) ** 2.4 + 0.7151522 * (green / 255) ** 2.4 + 0.072175 * (blue / 255) ** 2.4

  return luminance > 0.022 ? luminance : luminance + (0.022 - luminance) ** 1.414
}

/**
 * Returns the APCA-W3 (0.0.98G) lightness contrast (Lc) of text on a background.
 * Positive for dark text on a light background, negative for light text on a dark one.
 * Constants from https://github.com/Myndex/apca-w3/blob/master/src/apca-w3.js
 */
export const apcaContrast = (text: Rgb, background: Rgb) => {
  const textY = screenLuminance(text)
  const backgroundY = screenLuminance(background)

  if (Math.abs(backgroundY - textY) < 0.0005) {
    return 0
  }

  if (backgroundY > textY) {
    const contrast = (backgroundY ** 0.56 - textY ** 0.57) * 1.14

    return contrast < 0.1 ? 0 : (contrast - 0.027) * 100
  }

  const contrast = (backgroundY ** 0.65 - textY ** 0.62) * 1.14

  return contrast > -0.1 ? 0 : (contrast + 0.027) * 100
}

const LIGHT_TEXT = { css: '#ffffff', rgb: [255, 255, 255] as Rgb }
const DARK_TEXT = { css: '#111111', rgb: [17, 17, 17] as Rgb }

export const pickReadableTextColor = (backgroundColor: string) => {
  const background = cssColorToRgb(backgroundColor)

  if (!background) {
    return LIGHT_TEXT.css
  }

  const lightTextContrast = Math.abs(apcaContrast(LIGHT_TEXT.rgb, background))
  const darkTextContrast = Math.abs(apcaContrast(DARK_TEXT.rgb, background))

  return lightTextContrast >= darkTextContrast ? LIGHT_TEXT.css : DARK_TEXT.css
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
