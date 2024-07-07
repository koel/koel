import { screen } from '@testing-library/vue'
import { commonStore } from '@/stores'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Modal from './KoelPlusModal.vue'

new class extends UnitTestCase {
  protected test () {
    it('shows button to purchase Koel Plus', async () => {
      commonStore.state.koel_plus.product_id = '42'
      this.renderComponent()

      screen.getByTestId('buttons')
      expect(screen.queryByTestId('activateForm')).toBeNull()
      await this.user.click(screen.getByText('Purchase Koel Plus'))
      expect(global.LemonSqueezy.Url.Open).toHaveBeenCalledWith(
        'https://store.koel.dev/checkout/buy/42?embed=1&media=0&desc=0'
      )
    })

    it('shows form to activate Koel Plus', async () => {
      commonStore.state.koel_plus.product_id = '42'
      this.renderComponent()
      await this.user.click(screen.getByText('I have a license key'))
      screen.getByTestId('activateForm')
    })
  }

  private renderComponent () {
    return this.render(Modal)
  }
}
