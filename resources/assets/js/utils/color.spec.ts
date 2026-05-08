import { describe, expect, it } from 'vite-plus/test'
import { isDarkColor } from './color'

describe('isDarkColor', () => {
  it.each([
    ['#000000', true],
    ['#000', true],
    ['#111111', true],
    ['rgb(0, 0, 0)', true],
    ['rgb(64, 64, 64)', true],
    ['red', true],
    ['rebeccapurple', true],
    ['hsl(0, 100%, 50%)', true],
    ['hsla(0, 100%, 50%, 0.5)', true],
  ])('treats %s as dark', (color, expected) => {
    expect(isDarkColor(color)).toBe(expected)
  })

  it.each([
    ['#ffffff', false],
    ['#fff', false],
    ['#fbab18', false],
    ['rgb(255, 255, 255)', false],
    ['rgb(200, 200, 200)', false],
    ['white', false],
    ['lightyellow', false],
    ['hsl(0, 0%, 100%)', false],
    ['hsl(60, 100%, 90%)', false],
  ])('treats %s as not dark', (color, expected) => {
    expect(isDarkColor(color)).toBe(expected)
  })

  it('honors alpha-prefixed rgba() syntax', () => {
    expect(isDarkColor('rgba(10, 10, 10, 0.5)')).toBe(true)
    expect(isDarkColor('rgba(240, 240, 240, 0.5)')).toBe(false)
  })

  it('returns true for an unparseable input', () => {
    expect(isDarkColor('not-a-color')).toBe(true)
  })
})
