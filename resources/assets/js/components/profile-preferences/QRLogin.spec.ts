import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import Component from './QRLogin.vue'

vi.mock('@vueuse/integrations/useQRCode', () => ({
  useQRCode: () => 'data:image/png;base64,my-qr-code',
}))

describe('qRLogin.vue', () => {
  const h = createHarness()

  beforeEach(() => vi.useFakeTimers({ shouldAdvanceTime: true }))
  afterEach(() => vi.useRealTimers())

  it('pauses after 5 auto-refresh cycles and resumes on click', async () => {
    const getTokenMock = h.mock(authService, 'getOneTimeToken').mockResolvedValue('my-token')
    h.render(Component)
    await vi.waitFor(() => expect(getTokenMock).toHaveBeenCalledTimes(1))

    // Advance through 5 one-minute cycles. The 5th hits the cycle cap and pauses
    // without fetching, so 4 of the 5 ticks result in fetches.
    for (let i = 0; i < 5; i++) {
      await vi.advanceTimersByTimeAsync(60 * 1000)
    }

    expect(getTokenMock).toHaveBeenCalledTimes(5) // 1 initial + 4 auto
    const resumeBtn = await screen.findByRole('button', { name: /click for a new code/i })

    await h.user.click(resumeBtn)
    expect(getTokenMock).toHaveBeenCalledTimes(6)
  })
})
