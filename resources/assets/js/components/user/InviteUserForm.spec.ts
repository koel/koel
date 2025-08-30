import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { invitationService } from '@/services/invitationService'
import Component from './InviteUserForm.vue'

describe('inviteUserForm.vue', () => {
  const h = createHarness()

  it('invites single email', async () => {
    const inviteMock = h.mock(invitationService, 'invite')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    h.render(Component)

    await h.type(screen.getByRole('textbox'), 'foo@bar.ai\n')
    await h.user.click(screen.getByRole('checkbox'))
    await h.user.click(screen.getByRole('button', { name: 'Invite' }))

    await waitFor(() => {
      expect(inviteMock).toHaveBeenCalledWith(['foo@bar.ai'], true)
      expect(alertMock).toHaveBeenCalledWith('Invitation(s) sent.')
    })
  })

  it('invites multiple emails', async () => {
    const inviteMock = h.mock(invitationService, 'invite')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    h.render(Component)

    await h.type(screen.getByRole('textbox'), 'foo@bar.ai\n\na@b.c\n\n')
    await h.user.click(screen.getByRole('checkbox'))
    await h.user.click(screen.getByRole('button', { name: 'Invite' }))

    await waitFor(() => {
      expect(inviteMock).toHaveBeenCalledWith(['foo@bar.ai', 'a@b.c'], true)
      expect(alertMock).toHaveBeenCalledWith('Invitation(s) sent.')
    })
  })

  it('does not invites if at least one email is invalid', async () => {
    const inviteMock = h.mock(invitationService, 'invite')

    h.render(Component)

    await h.type(screen.getByRole('textbox'), 'invalid\n\na@b.c\n\n')
    await h.user.click(screen.getByRole('checkbox'))
    await h.user.click(screen.getByRole('button', { name: 'Invite' }))

    await waitFor(() => expect(inviteMock).not.toHaveBeenCalled())
  })
})
