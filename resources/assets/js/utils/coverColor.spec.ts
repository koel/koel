import { describe, expect, it } from 'vite-plus/test'
import { brightenToVisible, darkenToBackground, pickVividColor, toThemeColors } from './coverColor'

describe('coverColor', () => {
  const pixelsOf = (...colors: [number, number, number][]) =>
    new Uint8ClampedArray(colors.flatMap(([red, green, blue]) => [red, green, blue, 255]))

  it('picks the most prominent vivid color', () => {
    const color = pickVividColor(pixelsOf([220, 40, 30], [210, 50, 40], [220, 40, 30], [30, 60, 200]))

    expect(color).not.toBeNull()
    expect(color![0]).toBeGreaterThan(200)
    expect(color![2]).toBeLessThan(50)
  })

  it('ignores grays, near-blacks and near-whites', () => {
    expect(pickVividColor(pixelsOf([128, 128, 128], [5, 5, 5], [250, 250, 250]))).toBeNull()
  })

  it('ignores see-through pixels', () => {
    expect(pickVividColor(new Uint8ClampedArray([220, 40, 30, 0]))).toBeNull()
  })

  it('lightens a dark color so it shows on a dark background', () => {
    expect(brightenToVisible([80, 10, 10])).toBe('hsl(0 78% 55%)')
  })

  it('keeps a color that is already light enough', () => {
    expect(brightenToVisible([240, 120, 40])).toBe('hsl(24 87% 55%)')
  })

  it('darkens a light color so white text reads on it', () => {
    expect(darkenToBackground([240, 120, 40])).toBe('hsl(24 87% 25%)')
  })

  it('keeps a color that is already dark enough', () => {
    expect(darkenToBackground([80, 10, 10])).toBe('hsl(0 78% 18%)')
  })

  it('makes a dark, muted theme background and a visible highlight from a cover color', () => {
    expect(toThemeColors([240, 120, 40])).toEqual({ background: 'hsl(24 35% 10%)', highlight: 'hsl(24 87% 55%)' })
  })

  it('keeps the theme highlight from getting too bright', () => {
    expect(toThemeColors([250, 220, 120]).highlight).toBe('hsl(46 93% 65%)')
  })
})
