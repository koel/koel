import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TwoFactorEnrollment.vue'

describe('twoFactorEnrollment.vue', () => {
  const h = createHarness()

  it('renders the QR code and code input', () => {
    h.render(Component, {
      props: { provisioningUri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel' },
    })

    screen.getByAltText('Two-factor authentication QR code')
    screen.getByPlaceholderText('123 456')
  })

  it('emits submit with the typed code', async () => {
    const { emitted } = h.render(Component, {
      props: { provisioningUri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel' },
    })

    await h.type(screen.getByPlaceholderText('123 456'), '123456')
    await h.user.click(screen.getByRole('button', { name: 'Confirm' }))

    expect(emitted().submit).toEqual([['123456']])
  })

  it('emits cancel when the cancel button is clicked', async () => {
    const { emitted } = h.render(Component, {
      props: { provisioningUri: 'otpauth://totp/Koel:foo@bar?secret=ABC&issuer=Koel' },
    })

    await h.user.click(screen.getByRole('button', { name: 'Cancel' }))

    expect(emitted().cancel).toBeTruthy()
  })
})
