import { expect, it, vi } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { authService } from '@/services'
import QRLogin from '@/components/profile-preferences/QRLogin.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      vi.mock('@vueuse/integrations/useQRCode', () => ({
        useQRCode: () => 'data:image/png;base64,my-qr-code'
      }))

      const getTokenMock = this.mock(authService, 'getOneTimeToken').mockResolvedValue('my-token')
      const { html } = this.render(QRLogin)

      expect(getTokenMock).toHaveBeenCalled()
      expect(html()).toMatchSnapshot()
    })
  }
}
