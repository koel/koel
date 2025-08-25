import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './KoelPlusModal.vue'

describe('koelPlusModal.vue', () => {
  const h = createHarness()

  it('shows button to purchase Koel Plus', async () => {
    commonStore.state.koel_plus.product_id = '42'
    h.render(Component)

    screen.getByTestId('buttons')
    expect(screen.queryByTestId('activateForm')).toBeNull()
    await h.user.click(screen.getByText('Purchase Koel Plus'))
    expect(globalThis.LemonSqueezy.Url.Open).toHaveBeenCalledWith(
      'https://store.koel.dev/checkout/buy/42?embed=1&media=0&desc=0',
    )
  })

  it('shows form to activate Koel Plus', async () => {
    commonStore.state.koel_plus.product_id = '42'
    h.render(Component)
    await h.user.click(screen.getByText('I have a license key'))
    screen.getByTestId('activateForm')
  })
})
