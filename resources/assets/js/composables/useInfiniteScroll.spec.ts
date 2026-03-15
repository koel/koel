import { describe, expect, it, vi } from 'vite-plus/test'
import { effectScope, nextTick, ref } from 'vue'
import { useInfiniteScroll } from './useInfiniteScroll'

describe('useInfiniteScroll', () => {
  it('returns ToTopButton and sentinel ref', () => {
    const scope = effectScope()

    scope.run(() => {
      const el = ref<HTMLElement>()
      const result = useInfiniteScroll(el, vi.fn())

      expect(result.ToTopButton).toBeTruthy()
      expect(result.sentinel).toBeDefined()
    })

    scope.stop()
  })

  it('calls loadMore when sentinel becomes visible', async () => {
    let intersectionCallback: IntersectionObserverCallback

    vi.stubGlobal(
      'IntersectionObserver',
      vi.fn().mockImplementation(function (cb: IntersectionObserverCallback) {
        intersectionCallback = cb
        return { observe: vi.fn(), unobserve: vi.fn(), disconnect: vi.fn() }
      }),
    )

    const scope = effectScope()

    await scope.run(async () => {
      const container = ref(document.createElement('div'))
      const loadMore = vi.fn()

      const { sentinel } = useInfiniteScroll(container, loadMore)
      sentinel.value = document.createElement('div')

      await nextTick()

      intersectionCallback!([{ isIntersecting: true } as IntersectionObserverEntry], {} as IntersectionObserver)

      expect(loadMore).toHaveBeenCalled()
    })

    scope.stop()
    vi.restoreAllMocks()
  })

  it('does not call loadMore when sentinel is not intersecting', async () => {
    let intersectionCallback: IntersectionObserverCallback

    vi.stubGlobal(
      'IntersectionObserver',
      vi.fn().mockImplementation(function (cb: IntersectionObserverCallback) {
        intersectionCallback = cb
        return { observe: vi.fn(), unobserve: vi.fn(), disconnect: vi.fn() }
      }),
    )

    const scope = effectScope()

    await scope.run(async () => {
      const container = ref(document.createElement('div'))
      const loadMore = vi.fn()

      const { sentinel } = useInfiniteScroll(container, loadMore)
      sentinel.value = document.createElement('div')

      await nextTick()

      intersectionCallback!([{ isIntersecting: false } as IntersectionObserverEntry], {} as IntersectionObserver)

      expect(loadMore).not.toHaveBeenCalled()
    })

    scope.stop()
    vi.restoreAllMocks()
  })
})
