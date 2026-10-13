import { describe, expect, it } from 'vite-plus/test'
import type { Rgb } from './color'
import { apcaContrast, cssColorToRgb, pickReadableTextColor } from './color'

describe('apcaContrast', () => {
  it.each([
    [[136, 136, 136], [255, 255, 255], 63.1],
    [[255, 255, 255], [0, 0, 0], -107.9],
    [[255, 255, 255], [241, 102, 27], -63.2],
    [[17, 17, 17], [241, 102, 27], 45.6],
  ] as [Rgb, Rgb, number][])('rates text %j on %j at Lc %d', (text, background, expected) => {
    expect(apcaContrast(text, background)).toBeCloseTo(expected, 1)
  })

  it('returns 0 for identical colors', () => {
    expect(apcaContrast([241, 102, 27], [241, 102, 27])).toBe(0)
  })
})

describe('pickReadableTextColor', () => {
  it.each([
    ['#f1661b', '#ffffff'],
    ['#000000', '#ffffff'],
    ['#7a78dd', '#ffffff'],
    ['#f9d423', '#111111'],
    ['#ffffff', '#111111'],
    ['rgba(240, 240, 240, 0.5)', '#111111'],
  ])('picks readable text on %s', (background, expected) => {
    expect(pickReadableTextColor(background)).toBe(expected)
  })

  it('falls back to light text for an unparseable input', () => {
    expect(pickReadableTextColor('not-a-color')).toBe('#ffffff')
  })
})

describe('cssColorToRgb', () => {
  it.each([
    ['#0a141e', [10, 20, 30]],
    ['rgb(10, 20, 30)', [10, 20, 30]],
    ['hsl(0, 100%, 50%)', [255, 0, 0]],
  ])('reads the channels of %s', (color, channels) => {
    expect(cssColorToRgb(color)).toEqual(channels)
  })
})
