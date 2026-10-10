import { describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { ScrollAwayHeaderKey } from '@/config/symbols'
import { useScreenHeaderScroller } from './useScreenHeaderScroller'

describe('useScreenHeaderScroller', () => {
  const h = createHarness()

  const ScrollBox = defineComponent({
    setup: () => {
      const box = ref<HTMLElement>()
      useScreenHeaderScroller(() => box.value)
      return { box }
    },
    template: '<div ref="box" />',
  })

  it('hands the scroll box to the screen header and takes it back on unmount', async () => {
    const screenHeader = { attachScroller: vi.fn(), detachScroller: vi.fn() }

    const { unmount } = h.render(ScrollBox, {
      global: { provide: { [ScrollAwayHeaderKey as symbol]: screenHeader } },
    })

    await h.tick()
    expect(screenHeader.attachScroller).toHaveBeenCalledWith(expect.any(HTMLElement))

    unmount()
    expect(screenHeader.detachScroller).toHaveBeenCalledWith(screenHeader.attachScroller.mock.calls[0][0])
  })
})
