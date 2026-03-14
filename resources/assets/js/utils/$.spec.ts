import { describe, expect, it, vi } from 'vite-plus/test'
import { $ } from './$'

describe('$', () => {
  it('scrolls to a target position', () => {
    vi.useFakeTimers()

    const el = { scrollTop: 0, scrollHeight: 500, clientHeight: 100 } as Element

    $.scrollTo(el, 100, 100)
    vi.advanceTimersByTime(200)

    expect(el.scrollTop).toBe(100)

    vi.useRealTimers()
  })

  it('does nothing for non-positive duration', () => {
    const el = { scrollTop: 0 } as Element
    $.scrollTo(el, 100, 0)
    expect(el.scrollTop).toBe(0)
  })
})
