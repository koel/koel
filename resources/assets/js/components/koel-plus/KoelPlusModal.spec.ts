import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
import { commonStore } from '@/stores/commonStore'

const loadLemonSqueezyMock = vi.fn()

vi.mock('@/composables/useKoelPlus', async importOriginal => {
  const { useKoelPlus } = await importOriginal<typeof import('@/composables/useKoelPlus')>()

  return {
    useKoelPlus: () => ({ ...useKoelPlus(), loadLemonSqueezy: loadLemonSqueezyMock }),
  }
})

import Component from './KoelPlusModal.vue'

describe('koelPlusModal.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      loadLemonSqueezyMock.mockReset()
      commonStore.state.koel_plus.product_id = '42'
    },
  })

  it('opens checkout once Lemon Squeezy has loaded', async () => {
    let finishLoading: Closure = () => {}

    loadLemonSqueezyMock.mockReturnValue(
      new Promise<void>(resolve => {
        finishLoading = resolve
      }),
    )

    const openCheckoutMock = h.mock(window.LemonSqueezy.Url, 'Open')
    const { emitted } = h.render(Component)

    screen.getByTestId('buttons')
    expect(screen.queryByTestId('activateForm')).toBeNull()

    await h.user.click(screen.getByText('Purchase Koel Plus'))

    expect(openCheckoutMock).not.toHaveBeenCalled()
    expect(emitted().close).toBeUndefined()
    expect(screen.getByRole('button', { name: 'Purchase Koel Plus' })).toHaveProperty('disabled', true)

    finishLoading()

    await waitFor(() =>
      expect(openCheckoutMock).toHaveBeenCalledWith('https://store.koel.dev/checkout/buy/42?embed=1&media=0&desc=0'),
    )
    expect(emitted().close).toHaveLength(1)
  })

  it('stays open and shows an error when Lemon Squeezy fails to load', async () => {
    loadLemonSqueezyMock.mockRejectedValue(new Error('Failed to load Lemon Squeezy'))
    const openCheckoutMock = h.mock(window.LemonSqueezy.Url, 'Open')
    const showErrorDialogMock = h.mock(DialogBoxStub.value, 'error')
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByText('Purchase Koel Plus'))

    await waitFor(() =>
      expect(showErrorDialogMock).toHaveBeenCalledWith('Failed to load the checkout. Please try again.'),
    )
    expect(openCheckoutMock).not.toHaveBeenCalled()
    expect(emitted().close).toBeUndefined()
    expect(screen.getByRole('button', { name: 'Purchase Koel Plus' })).toHaveProperty('disabled', false)
  })

  it('stays open and shows an error when checkout fails to open', async () => {
    loadLemonSqueezyMock.mockResolvedValue(undefined)

    h.mock(window.LemonSqueezy.Url, 'Open', () => {
      throw new TypeError('Lemon Squeezy is not initialized')
    })

    const showErrorDialogMock = h.mock(DialogBoxStub.value, 'error')
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByText('Purchase Koel Plus'))

    await waitFor(() =>
      expect(showErrorDialogMock).toHaveBeenCalledWith('Failed to load the checkout. Please try again.'),
    )
    expect(emitted().close).toBeUndefined()
  })

  it('shows form to activate Koel Plus', async () => {
    h.render(Component)
    await h.user.click(screen.getByText('I have a license key'))
    screen.getByTestId('activateForm')
  })
})
