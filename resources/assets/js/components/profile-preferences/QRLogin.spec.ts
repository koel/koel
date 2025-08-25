import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './QRLogin.vue'

describe('qRLogin.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      vi.mock('@vueuse/integrations/useQRCode', () => ({
        useQRCode: () => 'data:image/png;base64,my-qr-code',
      }))
    },
  })

  it('renders', async () => {
    const getTokenMock = h.mock(authService, 'getOneTimeToken').mockResolvedValue('my-token')
    const { html } = h.render(Component)

    expect(getTokenMock).toHaveBeenCalled()
    expect(html()).toMatchSnapshot()
  })

  it('refreshes QR code', async () => {
    const getTokenMock = h.mock(authService, 'getOneTimeToken').mockResolvedValue('my-token')
    const { html } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Refresh now' }))

    expect(getTokenMock).toHaveBeenCalled()
    expect(html()).toMatchSnapshot()
  })
})
