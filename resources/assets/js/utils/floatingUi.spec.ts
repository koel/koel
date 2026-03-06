import { describe, expect, it, vi } from 'vitest'

vi.mock('@floating-ui/dom', () => ({
  computePosition: vi.fn().mockResolvedValue({
    x: 100,
    y: 200,
    middlewareData: { arrow: { x: 10, y: 20 } },
  }),
}))

import { updateFloatingUi } from './floatingUi'

describe('updateFloatingUi', () => {
  it('positions floating element', async () => {
    const reference = document.createElement('div')
    const floating = document.createElement('div')

    await updateFloatingUi(reference, floating, { placement: 'bottom' })

    expect(floating.style.left).toBe('100px')
    expect(floating.style.top).toBe('200px')
  })

  it('positions arrow element when provided', async () => {
    const reference = document.createElement('div')
    const floating = document.createElement('div')
    const arrow = document.createElement('div')

    await updateFloatingUi(reference, floating, { placement: 'bottom' }, arrow)

    expect(arrow.style.left).toBe('10px')
    expect(arrow.style.top).toBe('-4px')
  })
})
