import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { authService, http } from '@/services'
import { userStore } from '@/stores'
import { invitationService } from './invitationService'

new class extends UnitTestCase {
  protected test () {
    it('accepts an invitation', async () => {
      const postMock = this.mock(http, 'post').mockResolvedValue({
        'audio-token': 'my-audio-token',
        token: 'my-token',
      })

      const setAudioTokenMock = this.mock(authService, 'setAudioToken')
      const setApiTokenMock = this.mock(authService, 'setApiToken')

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
      const addMock = this.mock(userStore, 'add')
      const postMock = this.mock(http, 'post').mockResolvedValue(prospects)

      await invitationService.invite([prospects[0].email, prospects[1].email], false)

      expect(postMock).toHaveBeenCalledWith('invitations', {
        emails: [prospects[0].email, prospects[1].email],
        is_admin: false,
      })

      expect(addMock).toHaveBeenCalledWith(prospects)
    })

    it('revokes an invitation', async () => {
      const user = factory.states('prospect')('user')
      const removeMock = this.mock(userStore, 'remove')
      const deleteMock = this.mock(http, 'delete')

      await invitationService.revoke(user)

      expect(deleteMock).toHaveBeenCalledWith('invitations', { email: user.email })
      expect(removeMock).toHaveBeenCalledWith(user)
    })
  }
}
