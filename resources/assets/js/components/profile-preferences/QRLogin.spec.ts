import { expect, it, vi } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { authService } from '@/services'
import { screen } from '@testing-library/vue'
import Component from './QRLogin.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    vi.mock('@vueuse/integrations/useQRCode', () => ({
      useQRCode: () => 'data:image/png;base64,my-qr-code'
    }))
  }

  protected test () {
    it('renders', async () => {
      const getTokenMock = this.mock(authService, 'getOneTimeToken').mockResolvedValue('my-token')
      const { html } = this.render(Component)

      expect(getTokenMock).toHaveBeenCalled()
      expect(html()).toMatchSnapshot()
    })

    it('refreshes QR code', async () => {
      const getTokenMock = this.mock(authService, 'getOneTimeToken').mockResolvedValue('my-token')
      const { html } = this.render(Component)

      await this.user.click(screen.getByRole('button', { name: 'Refresh now' }))

      expect(getTokenMock).toHaveBeenCalled()
      expect(html()).toMatchSnapshot()
    })
  }
}
