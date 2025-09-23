import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { userStore } from '@/stores/userStore'
import { invitationService } from './invitationService'

describe('invitationService', () => {
  const h = createHarness()

  it('accepts an invitation', async () => {
    const postMock = h.mock(http, 'post').mockResolvedValue({
      'audio-token': 'my-audio-token',
      'token': 'my-token',
    })

    const setAudioTokenMock = h.mock(authService, 'setAudioToken')
    const setApiTokenMock = h.mock(authService, 'setApiToken')

    await invitationService.accept('invite-token', 'foo', 'bar')

    expect(postMock).toHaveBeenCalledWith('invitations/accept', {
      token: 'invite-token',
      name: 'foo',
      password: 'bar',
    })

    expect(setAudioTokenMock).toHaveBeenCalledWith('my-audio-token')
    expect(setApiTokenMock).toHaveBeenCalledWith('my-token')
  })

  it('invites users', async () => {
    const prospects = factory.states('prospect')('user', 2)
    const addMock = h.mock(userStore, 'add')
    const postMock = h.mock(http, 'post').mockResolvedValue(prospects)

    await invitationService.invite([prospects[0].email, prospects[1].email], 'admin')

    expect(postMock).toHaveBeenCalledWith('invitations', {
      emails: [prospects[0].email, prospects[1].email],
      role: 'admin',
    })

    expect(addMock).toHaveBeenCalledWith(prospects)
  })

  it('revokes an invitation', async () => {
    const user = factory.states('prospect')('user')
    const removeMock = h.mock(userStore, 'remove')
    const deleteMock = h.mock(http, 'delete')

    await invitationService.revoke(user)

    expect(deleteMock).toHaveBeenCalledWith('invitations', { email: user.email })
    expect(removeMock).toHaveBeenCalledWith(user)
  })
})
