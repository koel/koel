import { describe, expect, it } from 'vitest'
import { fireEvent, screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { invitationService } from '@/services/invitationService'
import Component from './InviteUserForm.vue'

describe('inviteUserForm.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      global: {
        stubs: {
          RolePicker: h.stub('role-picker', true),
        },
      },
    })
  }

  it('invites single email', async () => {
    const inviteMock = h.mock(invitationService, 'invite')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    renderComponent()

    await h.type(screen.getByLabelText('Emails'), 'foo@bar.ai\n')
    await fireEvent.update(screen.getByTestId('role-picker'), 'manager')
    await h.user.click(screen.getByRole('button', { name: 'Invite' }))

    await waitFor(() => {
      expect(inviteMock).toHaveBeenCalledWith(['foo@bar.ai'], 'manager')
      expect(alertMock).toHaveBeenCalledWith('Invitation(s) sent.')
    })
  })

  it('invites multiple emails', async () => {
    const inviteMock = h.mock(invitationService, 'invite')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    renderComponent()

    await h.type(screen.getByLabelText('Emails'), 'foo@bar.ai\n\na@b.c\n\n')
    await h.user.click(screen.getByRole('button', { name: 'Invite' }))

    await waitFor(() => {
      expect(inviteMock).toHaveBeenCalledWith(['foo@bar.ai', 'a@b.c'], 'user')
      expect(alertMock).toHaveBeenCalledWith('Invitation(s) sent.')
    })
  })

  it('does not invites if at least one email is invalid', async () => {
    const inviteMock = h.mock(invitationService, 'invite')

    renderComponent()

    await h.type(screen.getByLabelText('Emails'), 'invalid\n\na@b.c\n\n')
    await h.user.click(screen.getByRole('button', { name: 'Invite' }))

    await waitFor(() => expect(inviteMock).not.toHaveBeenCalled())
  })
})
