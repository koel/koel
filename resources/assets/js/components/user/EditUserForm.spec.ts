import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { ModalContextKey } from '@/symbols'
import { screen, waitFor } from '@testing-library/vue'
import { userStore } from '@/stores'
import { MessageToasterStub } from '@/__tests__/stubs'
import EditUserForm from './EditUserForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('edits a user', async () => {
      const updateMock = this.mock(userStore, 'update')
      const alertMock = this.mock(MessageToasterStub.value, 'success')

      const user = ref(factory<User>('user', { name: 'John Doe' }))

      this.render(EditUserForm, {
        global: {
          provide: {
            [<symbol>ModalContextKey]: [ref({ user })]
          }
        }
      })

      await this.type(screen.getByRole('textbox', { name: 'Name' }), 'Jane Doe')
      await this.type(screen.getByTitle('Password'), 'new-password-duck')
      await this.user.click(screen.getByRole('button', { name: 'Update' }))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith(user.value, {
          name: 'Jane Doe',
          email: user.value.email,
          is_admin: user.value.is_admin,
          password: 'new-password-duck'
        })

        expect(alertMock).toHaveBeenCalledWith('User profile updated.')
      })
    })
  }
}
