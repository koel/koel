import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ExpandableContentBlock.vue'

describe('expandableContentBlock.vue', () => {
  const h = createHarness()

  afterEach(() => {
    vi.unstubAllGlobals()
    vi.restoreAllMocks()
  })

  const renderWithContentHeight = async (scrollHeight: number) => {
    vi.spyOn(HTMLElement.prototype, 'scrollHeight', 'get').mockReturnValue(scrollHeight)
    vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(72)

    vi.stubGlobal(
      'ResizeObserver',
      vi.fn().mockImplementation(function (callback: ResizeObserverCallback) {
        return {
          observe: () => callback([], {} as ResizeObserver),
          unobserve: vi.fn(),
          disconnect: vi.fn(),
        }
      }),
    )

    h.render(Component, {
      slots: { default: 'Content' },
    })

    await h.tick()
  }

  it('hides the More button after clicking it', async () => {
    await renderWithContentHeight(300)

    await h.user.click(screen.getByRole('button', { name: 'More' }))
    expect(screen.queryByRole('button', { name: 'More' })).toBeNull()
  })

  it('has no More button if the content fits', async () => {
    await renderWithContentHeight(72)

    expect(screen.queryByRole('button', { name: 'More' })).toBeNull()
  })
})
