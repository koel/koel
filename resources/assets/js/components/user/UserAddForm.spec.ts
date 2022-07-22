import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { fireEvent, waitFor } from '@testing-library/vue'
import { userStore } from '@/stores'
import { alerts } from '@/utils'
import UserAddForm from './UserAddForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('creates a new user', async () => {
      const storeMock = this.mock(userStore, 'store')
      const alertMock = this.mock(alerts, 'success')

      const { getByLabelText, getByRole } = this.render(UserAddForm)

      await fireEvent.update(getByLabelText('Name'), 'John Doe')
      await fireEvent.update(getByLabelText('Email'), 'john@doe.com')
      await fireEvent.update(getByLabelText('Password'), 'secret-password')
      await fireEvent.click(getByRole('checkbox'))
      await fireEvent.click(getByRole('button', { name: 'Save' }))

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
