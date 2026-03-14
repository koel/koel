import { describe, expect, it, vi } from 'vite-plus/test'

const successMock = vi.fn()
const infoMock = vi.fn()
const warningMock = vi.fn()
const errorMock = vi.fn()

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  requireInjection: () => ({
    value: {
      success: successMock,
      info: infoMock,
      warning: warningMock,
      error: errorMock,
    },
  }),
}))

import { useMessageToaster } from './useMessageToaster'

describe('useMessageToaster', () => {
  it('exposes toast methods', () => {
    const { toastSuccess, toastInfo, toastWarning, toastError } = useMessageToaster()

    toastSuccess('Done!')
    expect(successMock).toHaveBeenCalledWith('Done!')

    toastInfo('FYI')
    expect(infoMock).toHaveBeenCalledWith('FYI')

    toastWarning('Careful')
    expect(warningMock).toHaveBeenCalledWith('Careful')

    toastError('Oops')
    expect(errorMock).toHaveBeenCalledWith('Oops')
  })
})
