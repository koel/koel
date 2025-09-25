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

    h.visit('/invitation/accept/73a36cfd-4afd-48ae-b031-ae5488858375').render(Component)

    await waitFor(() => expect(getProspectMock).toHaveBeenCalledWith('73a36cfd-4afd-48ae-b031-ae5488858375'))
    await h.tick(2)

    await h.user.type(screen.getByTestId('name'), 'Bruce Dickinson')
    await h.user.type(screen.getByTestId('password'), 'top-secret')
    await h.user.click(screen.getByTestId('submit'))

    expect(acceptMock).toHaveBeenCalledWith('73a36cfd-4afd-48ae-b031-ae5488858375', 'Bruce Dickinson', 'top-secret')
  })
})
