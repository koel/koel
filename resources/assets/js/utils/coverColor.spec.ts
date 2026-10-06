import { describe, expect, it } from 'vite-plus/test'
import { brightenToVisible, pickVividColor } from './coverColor'

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
})
