import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useScrollAwayHeader } from './useScrollAwayHeader'

describe('useScrollAwayHeader', () => {
  const h = createHarness()

  let reportHeaderHeight: (height: number) => void = () => {}

  beforeEach(() => {
    vi.stubGlobal(
      'ResizeObserver',
      class {
        constructor(callback: ResizeObserverCallback) {
          reportHeaderHeight = height =>
            callback([{ borderBoxSize: [{ blockSize: height }] } as unknown as ResizeObserverEntry], this as any)
        }

        observe() {}

        unobserve() {}

        disconnect() {}
      },
    )
  })

  afterEach(() => vi.unstubAllGlobals())

  const setUp = async () => {
    const host = ref<HTMLElement>()
    let header!: ReturnType<typeof useScrollAwayHeader>

    h.render(
      defineComponent({
        setup: () => {
          header = useScrollAwayHeader(host)
          return () => null
        },
      }),
    )

    host.value = document.createElement('header')
    await h.tick()
    reportHeaderHeight(200)

    const scroller = document.createElement('div')
    header.api.attachScroller(scroller)

    const scrollTo = (top: number) => {
      Object.defineProperty(scroller, 'scrollTop', { value: top, configurable: true })
      scroller.dispatchEvent(new Event('scroll'))
    }

    return { header, scrollTo }
  }

  it('moves the header up with the content until it is out of sight', async () => {
    const { header, scrollTo } = await setUp()

    scrollTo(80)
    expect(header.hiddenHeight.value).toBe(80)

    scrollTo(500)
    expect(header.hiddenHeight.value).toBe(200)
    expect(header.api.columnHeaderTop.value).toBe(0)
  })

  it('slides the header back in after scrolling up a little', async () => {
    const { header, scrollTo } = await setUp()

    scrollTo(500)
    scrollTo(495)
    expect(header.api.revealed.value).toBe(false)

    scrollTo(488)
    reportHeaderHeight(90)

    expect(header.api.revealed.value).toBe(true)
    expect(header.hiddenHeight.value).toBe(0)
    expect(header.api.columnHeaderTop.value).toBe(90)
  })

  it('hides the slid-in header again on scrolling down', async () => {
    const { header, scrollTo } = await setUp()

    scrollTo(500)
    scrollTo(480)
    scrollTo(490)

    expect(header.api.revealed.value).toBe(false)
  })

  it('puts the header back in place at the top', async () => {
    const { header, scrollTo } = await setUp()

    scrollTo(500)
    scrollTo(480)
    scrollTo(0)

    expect(header.api.revealed.value).toBe(false)
    expect(header.hiddenHeight.value).toBe(0)
  })
})
