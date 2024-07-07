import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { plusService } from '@/services'
import Form from './ActivateLicenseForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('activates license', async () => {
      this.renderComponent()
      const activateMock = this.mock(plusService, 'activateLicense').mockResolvedValueOnce('')

      await this.type(screen.getByRole('textbox'), 'my-license-key')
      await this.user.click(screen.getByText('Activate'))
      expect(activateMock).toHaveBeenCalledWith('my-license-key')
    })
  }

  private renderComponent () {
    return this.render(Form)
  }
}
