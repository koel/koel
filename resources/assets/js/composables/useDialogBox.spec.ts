import { describe, expect, it, vi } from 'vitest'

const successMock = vi.fn()
const infoMock = vi.fn()
const warningMock = vi.fn()
const errorMock = vi.fn()
const confirmMock = vi.fn()

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  requireInjection: () => ({
    value: {
      success: successMock,
      info: infoMock,
      warning: warningMock,
      error: errorMock,
      confirm: confirmMock,
    },
  }),
}))

import { useDialogBox } from './useDialogBox'

describe('useDialogBox', () => {
  it('exposes dialog methods', () => {
    const { showSuccessDialog, showConfirmDialog } = useDialogBox()

    showSuccessDialog('Success!')
    expect(successMock).toHaveBeenCalledWith('Success!')

    showConfirmDialog('Are you sure?')
    expect(confirmMock).toHaveBeenCalledWith('Are you sure?')
  })
})
