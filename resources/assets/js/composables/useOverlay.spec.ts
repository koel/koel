import { describe, expect, it, vi } from 'vitest'

const showMock = vi.fn()
const hideMock = vi.fn()

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  requireInjection: () => ({
    value: {
      show: showMock,
      hide: hideMock,
    },
  }),
}))

import { useOverlay } from './useOverlay'

describe('useOverlay', () => {
  it('exposes show and hide methods', () => {
    const { showOverlay, hideOverlay } = useOverlay()

    showOverlay({ message: 'Loading...' })
    expect(showMock).toHaveBeenCalledWith({ message: 'Loading...' })

    hideOverlay()
    expect(hideMock).toHaveBeenCalled()
  })
})
