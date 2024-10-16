import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { invitationService } from '@/services/invitationService'
import AcceptInvitation from './AcceptInvitation.vue'

new class extends UnitTestCase {
  protected test () {
    it('accepts invitation', async () => {
      const getProspectMock = this.mock(invitationService, 'getUserProspect')
        .mockResolvedValue(factory.states('prospect')('user'))

      const acceptMock = this.mock(invitationService, 'accept').mockResolvedValue({
        'token': 'my-api-token',
        'audio-token': 'my-audio-token',
      })

      await this.router.activateRoute({
        path: '_',
        screen: 'Invitation.Accept',
      }, {
        token: 'my-token',
      })

      this.render(AcceptInvitation)
      await waitFor(() => expect(getProspectMock).toHaveBeenCalledWith('my-token'))

      await this.tick(2)

      await this.user.type(screen.getByTestId('name'), 'Bruce Dickinson')
      await this.user.type(screen.getByTestId('password'), 'top-secret')
      await this.user.click(screen.getByTestId('submit'))

      expect(acceptMock).toHaveBeenCalledWith('my-token', 'Bruce Dickinson', 'top-secret')
    })
  }
}
