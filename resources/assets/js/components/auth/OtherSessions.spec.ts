import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { DialogBoxStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
import Component from './OtherSessions.vue'

describe('otherSessions.vue', () => {
  const h = createHarness()

  it('logs out other sessions once confirmed', async () => {
    const logOutMock = h.mock(authService, 'logOutOtherSessions').mockResolvedValue(undefined)
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Log Out Other Sessions' }))

    expect(logOutMock).toHaveBeenCalled()
  })

  it('keeps other sessions when not confirmed', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
    const logOutMock = h.mock(authService, 'logOutOtherSessions')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Log Out Other Sessions' }))

    expect(logOutMock).not.toHaveBeenCalled()
  })
})
