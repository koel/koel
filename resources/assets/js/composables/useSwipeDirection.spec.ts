import { describe, expect, it, vi } from 'vitest'
import { defineComponent, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useSwipeDirection } from './useSwipeDirection'

describe('useSwipeDirection', () => {
  const h = createHarness()

  const createWrapper = (
    callback: (dir: 'up' | 'down') => void,
    options?: { threshold?: number; wheelThreshold?: number },
  ) => {
    return defineComponent({
      setup() {
        const el = ref<HTMLElement>()

        useSwipeDirection(() => el.value, callback, options)

        return { el }
      },
      template: '<div ref="el" data-testid="target" style="height: 200px" />',
    })
  }

  it('detects swipe up via touch events', async () => {
    const callback = vi.fn()
    const { getByTestId } = h.render(createWrapper(callback))
    const target = getByTestId('target')

    target.dispatchEvent(
      new TouchEvent('touchstart', {
        touches: [{ clientY: 200 } as Touch],
      }),
    )

    target.dispatchEvent(
      new TouchEvent('touchend', {
        changedTouches: [{ clientY: 100 } as Touch],
      }),
    )

    expect(callback).toHaveBeenCalledWith('up')
  })

  it('detects swipe down via touch events', async () => {
    const callback = vi.fn()
    const { getByTestId } = h.render(createWrapper(callback))
    const target = getByTestId('target')

    target.dispatchEvent(
      new TouchEvent('touchstart', {
        touches: [{ clientY: 100 } as Touch],
      }),
    )

    target.dispatchEvent(
      new TouchEvent('touchend', {
        changedTouches: [{ clientY: 200 } as Touch],
      }),
    )

    expect(callback).toHaveBeenCalledWith('down')
  })

  it('ignores swipe below threshold', async () => {
    const callback = vi.fn()
    const { getByTestId } = h.render(createWrapper(callback))
    const target = getByTestId('target')

    target.dispatchEvent(
      new TouchEvent('touchstart', {
        touches: [{ clientY: 100 } as Touch],
      }),
    )

    target.dispatchEvent(
      new TouchEvent('touchend', {
        changedTouches: [{ clientY: 110 } as Touch],
      }),
    )

    expect(callback).not.toHaveBeenCalled()
  })

  it('detects scroll down via wheel event', async () => {
    const callback = vi.fn()
    const { getByTestId } = h.render(createWrapper(callback))
    const target = getByTestId('target')

    target.dispatchEvent(new WheelEvent('wheel', { deltaY: 50 }))

    expect(callback).toHaveBeenCalledWith('down')
  })

  it('detects scroll up via wheel event', async () => {
    const callback = vi.fn()
    const { getByTestId } = h.render(createWrapper(callback))
    const target = getByTestId('target')

    target.dispatchEvent(new WheelEvent('wheel', { deltaY: -50 }))

    expect(callback).toHaveBeenCalledWith('up')
  })

  it('ignores wheel below threshold', async () => {
    const callback = vi.fn()
    const { getByTestId } = h.render(createWrapper(callback))
    const target = getByTestId('target')

    target.dispatchEvent(new WheelEvent('wheel', { deltaY: 2 }))

    expect(callback).not.toHaveBeenCalled()
  })
})
