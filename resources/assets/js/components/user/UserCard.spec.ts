import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { screen } from '@testing-library/vue'
import { eventBus } from '@/utils'
import { userStore } from '@/stores'
import { DialogBoxStub } from '@/__tests__/stubs'
import UserCard from './UserCard.vue'
import { invitationService } from '@/services'

new class extends UnitTestCase {
  private renderComponent (user: User) {
    return this.render(UserCard, {
      props: {
        user
      }
    })
  }

  protected test () {
    it('has different behaviors for current user', () => {
      const user = factory<User>('user')
      this.actingAs(user).renderComponent(user)

      screen.getByTitle('This is you!')
      screen.getByText('Your Profile')
    })

    it('edits user', async () => {
      const user = factory<User>('user')
      const emitMock = this.mock(eventBus, 'emit')
      this.renderComponent(user)

      await this.user.click(screen.getByRole('button', { name: 'Edit' }))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_USER_FORM', user)
    })

    it('redirects to Profile screen if edit current user', async () => {
      const mock = this.mock(this.router, 'go')
      const user = factory<User>('user')
      this.actingAs(user).renderComponent(user)

      await this.user.click(screen.getByRole('button', { name: 'Your Profile' }))

      expect(mock).toHaveBeenCalledWith('profile')
    })

    it('deletes user if confirmed', async () => {
      this.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(true)
      const user = factory<User>('user')
      this.actingAsAdmin().renderComponent(user)
      const destroyMock = this.mock(userStore, 'destroy')

      await this.user.click(screen.getByRole('button', { name: 'Delete' }))

      expect(destroyMock).toHaveBeenCalledWith(user)
    })

    it('does not delete user if not confirmed', async () => {
      this.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
      const user = factory<User>('user')
      this.actingAsAdmin().renderComponent(user)
      const destroyMock = this.mock(userStore, 'destroy')

      await this.user.click(screen.getByRole('button', { name: 'Delete' }))

      expect(destroyMock).not.toHaveBeenCalled()
    })

    it('revokes invite for prospects', async () => {
      this.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(true)
      const prospect = factory.states('prospect')<User>('user')
      this.actingAsAdmin().renderComponent(prospect)
      const revokeMock = this.mock(invitationService, 'revoke')

      await this.user.click(screen.getByRole('button', { name: 'Revoke' }))

      expect (revokeMock).toHaveBeenCalledWith(prospect)
    })

    it('does not revoke invite for prospects if not confirmed', async () => {
      this.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
      const prospect = factory.states('prospect')<User>('user')
      this.actingAsAdmin().renderComponent(prospect)
      const revokeMock = this.mock(invitationService, 'revoke')

      await this.user.click(screen.getByRole('button', { name: 'Revoke' }))

      expect(revokeMock).not.toHaveBeenCalled()
    })
  }
}
