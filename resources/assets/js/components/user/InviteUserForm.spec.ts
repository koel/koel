import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { screen, waitFor } from '@testing-library/vue'
import { MessageToasterStub } from '@/__tests__/stubs'
import { invitationService } from '@/services'
import InviteUserForm from './InviteUserForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('invites single email', async () => {
      const inviteMock = this.mock(invitationService, 'invite')
      const alertMock = this.mock(MessageToasterStub.value, 'success')

      this.render(InviteUserForm)

      await this.type(screen.getByRole('textbox'), 'foo@bar.ai\n')
      await this.user.click(screen.getByRole('checkbox'))
      await this.user.click(screen.getByRole('button', { name: 'Invite' }))

      await waitFor(() => {
        expect(inviteMock).toHaveBeenCalledWith(['foo@bar.ai'], true)
        expect(alertMock).toHaveBeenCalledWith('Invitation sent.')
      })
    })

    it('invites multiple emails', async () => {
      const inviteMock = this.mock(invitationService, 'invite')
      const alertMock = this.mock(MessageToasterStub.value, 'success')

      this.render(InviteUserForm)

      await this.type(screen.getByRole('textbox'), 'foo@bar.ai\n\na@b.c\n\n')
      await this.user.click(screen.getByRole('checkbox'))
      await this.user.click(screen.getByRole('button', { name: 'Invite' }))

      await waitFor(() => {
        expect(inviteMock).toHaveBeenCalledWith(['foo@bar.ai', 'a@b.c'], true)
        expect(alertMock).toHaveBeenCalledWith('Invitations sent.')
      })
    })

    it('does not invites if at least one email is invalid', async () => {
      const inviteMock = this.mock(invitationService, 'invite')

      this.render(InviteUserForm)

      await this.type(screen.getByRole('textbox'), 'invalid\n\na@b.c\n\n')
      await this.user.click(screen.getByRole('checkbox'))
      await this.user.click(screen.getByRole('button', { name: 'Invite' }))

      await waitFor(() => expect(inviteMock).not.toHaveBeenCalled())
    })
  }
}
