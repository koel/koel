import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { MessageToasterStub } from '@/__tests__/stubs'
import { authService } from '@/services/authService'
import Component from './ProfileForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('updates profile', async () => {
      const updateMock = this.mock(authService, 'updateProfile')
      const alertMock = this.mock(MessageToasterStub.value, 'success')

      this.renderComponent(factory('user', {
        avatar: 'https://gravatar.com/foo',
      }))

      await this.type(screen.getByTestId('currentPassword'), 'old-password')
      await this.type(screen.getByTestId('email'), 'koel@example.com')
      await this.type(screen.getByTestId('name'), 'Koel User')
      await this.type(screen.getByTestId('newPassword'), 'new-password')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(updateMock).toHaveBeenCalledWith({
        name: 'Koel User',
        email: 'koel@example.com',
        current_password: 'old-password',
        new_password: 'new-password',
        avatar: 'https://gravatar.com/foo',
      })

      expect(alertMock).toHaveBeenCalledWith('Profile updated.')
    })
  }

  private renderComponent (user: User) {
    return this.be(user).render(Component)
  }
}
