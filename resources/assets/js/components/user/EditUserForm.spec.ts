import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { UserKey } from '@/symbols'
import { fireEvent, waitFor } from '@testing-library/vue'
import { userStore } from '@/stores'
import EditUserForm from './EditUserForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('edits a user', async () => {
      const updateMock = this.mock(userStore, 'update')

      const user = ref(factory<User>('user', { name: 'John Doe' }))

      const { getByLabelText, getByRole } = this.render(EditUserForm, {
        global: {
          provide: {
            [<symbol>UserKey]: [user]
          }
        }
      })

      await fireEvent.update(getByLabelText('Name'), 'Jane Doe')
      await fireEvent.update(getByLabelText('Password'), 'new-password-duck')
      await fireEvent.click(getByRole('button', { name: 'Update' }))

      await waitFor(() => {
        expect(updateMock).toHaveBeenCalledWith(user.value, {
          name: 'Jane Doe',
          email: user.value.email,
          is_admin: user.value.is_admin,
          password: 'new-password-duck'
        })
      })
    })
  }
}
