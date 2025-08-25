import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { invitationService } from '@/services/invitationService'
import Component from './AcceptInvitation.vue'

describe('acceptInvitation.vue', () => {
  const h = createHarness()

  it('accepts invitation', async () => {
    const getProspectMock = h.mock(invitationService, 'getUserProspect')
      .mockResolvedValue(factory.states('prospect')('user'))

    const acceptMock = h.mock(invitationService, 'accept').mockResolvedValue({
      'token': 'my-api-token',
      'audio-token': 'my-audio-token',
    })

    await h.router.activateRoute({
      path: '_',
      screen: 'Invitation.Accept',
    }, {
      token: 'my-token',
    })

    h.render(Component)
    await waitFor(() => expect(getProspectMock).toHaveBeenCalledWith('my-token'))

    await h.tick(2)

    await h.user.type(screen.getByTestId('name'), 'Bruce Dickinson')
    await h.user.type(screen.getByTestId('password'), 'top-secret')
    await h.user.click(screen.getByTestId('submit'))

    expect(acceptMock).toHaveBeenCalledWith('my-token', 'Bruce Dickinson', 'top-secret')
  })
})
