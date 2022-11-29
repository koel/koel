import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { screen, waitFor } from '@testing-library/vue'
import { userStore } from '@/stores'
import { MessageToasterStub } from '@/__tests__/stubs'
import AddUserForm from './AddUserForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('creates a new user', async () => {
      const storeMock = this.mock(userStore, 'store')
      const alertMock = this.mock(MessageToasterStub.value, 'success')

      this.render(AddUserForm)

      await this.type(screen.getByRole('textbox', { name: 'Name' }), 'John Doe')
      await this.type(screen.getByRole('textbox', { name: 'Email' }), 'john@doe.com')
      await this.type(screen.getByLabelText('Password'), 'secret-password')
      await this.user.click(screen.getByRole('checkbox'))
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      await waitFor(() => {
        expect(storeMock).toHaveBeenCalledWith({
          name: 'John Doe',
          email: 'john@doe.com',
          password: 'secret-password',
          is_admin: true
        })

        expect(alertMock).toHaveBeenCalledWith('New user "John Doe" created.')
      })
    })
  }
}
