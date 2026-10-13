import { describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'

import Component from './WithGradientBorder.vue'

describe('withGradientBorder.vue', () => {
  const h = createHarness()

  const countPointerMoveCalls = (spy: { mock: { calls: unknown[][] } }) =>
    spy.mock.calls.filter(([type]) => type === 'pointermove').length

  it('shares one window pointer listener across all instances', () => {
    const addSpy = vi.spyOn(window, 'addEventListener')
    const removeSpy = vi.spyOn(window, 'removeEventListener')

    const renders = [h.render(Component), h.render(Component), h.render(Component)]

    expect(countPointerMoveCalls(addSpy)).toBe(1)

    renders[0].unmount()
    renders[1].unmount()

    expect(countPointerMoveCalls(removeSpy)).toBe(0)

    renders[2].unmount()

    expect(countPointerMoveCalls(removeSpy)).toBe(1)
  })

  it('follows the pointer while visible', async () => {
    let onIntersect: IntersectionObserverCallback | undefined

    vi.mocked(window.IntersectionObserver).mockImplementationOnce(function (callback: IntersectionObserverCallback) {
      onIntersect = callback

      return { observe: vi.fn(), disconnect: vi.fn() } as unknown as IntersectionObserver
    })

    h.render(Component, { slots: { default: '<span data-testid="content" />' } })

    const measureSpy = vi.spyOn(screen.getByTestId('content').parentElement!, 'getBoundingClientRect')
    onIntersect!([{ isIntersecting: true } as IntersectionObserverEntry], {} as IntersectionObserver)
    measureSpy.mockClear()

    await new Promise(resolve => setTimeout(resolve, 60))
    await fireEvent(window, new MouseEvent('pointermove', { clientX: 42, clientY: 24 }))

    await vi.waitFor(() => expect(measureSpy).toHaveBeenCalled())
  })
})
