/**
 * Returns true when the given CSS color is perceptually dark.
 *
 * The threshold matches what the `color` npm package's `isDark()` used:
 * a YIQ-weighted brightness < 128 on the 0-255 scale (W3C WCAG 1.0).
 * The probe-element trick lets the browser parse any CSS color syntax
 * (hex, rgb/rgba, hsl/hsla, oklch, named colors) and normalize it to rgb().
 */
export const isDarkColor = (cssColor: string) => {
  const probe = document.createElement('div')
  probe.style.color = cssColor
  document.body.appendChild(probe)
  const computed = window.getComputedStyle(probe).color
  document.body.removeChild(probe)

  const channels = computed.match(/\d+(?:\.\d+)?/g)

  if (!channels || channels.length < 3) {
    return true
  }

  const [red, green, blue] = channels.map(Number)
  return (red * 299 + green * 587 + blue * 114) / 1000 < 128
}
