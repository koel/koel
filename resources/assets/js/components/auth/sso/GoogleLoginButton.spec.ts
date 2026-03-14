import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './GoogleLoginButton.vue'

const openPopupMock = vi.fn()

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  openPopup: (...args: any[]) => openPopupMock(...args),
}))

describe('googleLoginButton.vue', () => {
  const h = createHarness()

  it('renders login button', () => {
    h.render(Component)
    screen.getByTitle('Log in with Google')
  })

  it('opens popup on click', async () => {
    h.render(Component)
    await h.user.click(screen.getByTitle('Log in with Google'))

    expect(openPopupMock).toHaveBeenCalledWith('/auth/google/redirect', 'Google Login', 768, 640, window)
  })
})
