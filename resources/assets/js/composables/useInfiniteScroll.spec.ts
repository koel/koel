import { describe, expect, it, vi } from 'vitest'
import { effectScope, ref } from 'vue'

vi.mock('@vueuse/core', async importOriginal => ({
  ...(await importOriginal<typeof import('@vueuse/core')>()),
  useInfiniteScroll: vi.fn(),
}))

import { useInfiniteScroll } from './useInfiniteScroll'

describe('useInfiniteScroll', () => {
  it('returns ToTopButton and makeScrollable', () => {
    const scope = effectScope()

    scope.run(() => {
      const el = ref<HTMLElement>()
      const loadMore = vi.fn()

      const result = useInfiniteScroll(el, loadMore)

      expect(result.ToTopButton).toBeTruthy()
      expect(typeof result.makeScrollable).toBe('function')
    })

    scope.stop()
  })

  it('calls loadMore when container is not scrollable', async () => {
    vi.useFakeTimers()
    const scope = effectScope()

    await scope.run(async () => {
      const container = document.createElement('div')
      Object.defineProperty(container, 'scrollHeight', { value: 100 })
      Object.defineProperty(container, 'clientHeight', { value: 200 })

      const el = ref<HTMLElement>(container)
      const loadMore = vi.fn().mockResolvedValue(undefined)

      const { makeScrollable } = useInfiniteScroll(el, loadMore)
      await makeScrollable()

      expect(loadMore).toHaveBeenCalled()
    })

    scope.stop()
    vi.useRealTimers()
  })
})
