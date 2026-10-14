import { defineComponent, nextTick } from 'vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useRouter } from './useRouter'

describe('useRouter', () => {
  const h = createHarness()

  const renderComponent = (setup: () => void) => h.render(defineComponent({ setup, render: () => null }))

  it('checks current screen', () => {
    h.visit('/home')

    let isCurrentScreen: ReturnType<typeof useRouter>['isCurrentScreen'] | undefined

    renderComponent(() => {
      isCurrentScreen = useRouter().isCurrentScreen
    })

    expect(isCurrentScreen!('Home')).toBe(true)
    expect(isCurrentScreen!('Queue')).toBe(false)
  })

  it('runs a screen handler only for the mounted component', async () => {
    h.visit('/home')
    await nextTick()

    const handler = vi.fn()

    for (let i = 0; i < 5; i++) {
      renderComponent(() => useRouter().onScreenActivated('Queue', handler)).unmount()
    }

    renderComponent(() => useRouter().onScreenActivated('Queue', handler))

    h.visit('/queue')
    await nextTick()

    expect(handler).toHaveBeenCalledOnce()
  })
})
