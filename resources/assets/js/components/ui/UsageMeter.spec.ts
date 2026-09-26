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

  it.each([
    [40, 100, 'inset(0 60% 0 0 round 9999px)'],
    [150, 100, 'inset(0 0% 0 0 round 9999px)'],
    [40, 0, 'inset(0 100% 0 0 round 9999px)'],
  ])('fills %d of %d up to the right point', (used, limit, clipPath) => {
    h.render(Component, { props: { used, limit } })

    expect((screen.getByRole('meter').firstElementChild as HTMLElement).style.clipPath).toBe(clipPath)
  })
})
