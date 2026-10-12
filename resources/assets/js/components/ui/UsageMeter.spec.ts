import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './UsageMeter.vue'

describe('usageMeter.vue', () => {
  const h = createHarness()

  it('reports how much of the limit is used', () => {
    h.render(Component, { props: { used: 40, limit: 100 } })

    const meter = screen.getByRole('meter')

    expect([meter.getAttribute('aria-valuenow'), meter.getAttribute('aria-valuemax')]).toEqual(['40', '100'])
  })

  it('caps the reported value at the limit', () => {
    h.render(Component, { props: { used: 150, limit: 100 } })

    expect(screen.getByRole('meter').getAttribute('aria-valuenow')).toBe('100')
  })

  it('is not a meter when the limit is unknown', () => {
    h.render(Component, { props: { used: 40, limit: 0 } })

    expect(screen.queryByRole('meter')).toBeNull()
  })

  it.each([
    [40, 100, 'inset(0 60% 0 0 round 9999px)'],
    [150, 100, 'inset(0 0% 0 0 round 9999px)'],
    [40, 0, 'inset(0 100% 0 0 round 9999px)'],
  ])('fills %d of %d up to the right point', (used, limit, clipPath) => {
    const { container } = h.render(Component, { props: { used, limit } })

    expect(container.querySelector<HTMLElement>('.bar')!.style.clipPath).toBe(clipPath)
  })
})
