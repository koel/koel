import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { userStore } from '@/stores/userStore'
import Component from './AddUserForm.vue'

describe('addUserForm.vue', () => {
  const h = createHarness()

  it('creates a new user', async () => {
    const storeMock = h.mock(userStore, 'store')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    h.render(Component)

    await h.type(screen.getByRole('textbox', { name: 'Name' }), 'John Doe')
    await h.type(screen.getByRole('textbox', { name: 'Email' }), 'john@doe.com')
    await h.type(screen.getByTitle('Password'), 'secret-password')
    await h.user.click(screen.getByRole('checkbox'))
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await waitFor(() => {
      expect(storeMock).toHaveBeenCalledWith({
        name: 'John Doe',
        email: 'john@doe.com',
        password: 'secret-password',
        is_admin: true,
      })

      expect(alertMock).toHaveBeenCalledWith('New user "John Doe" created.')
    })
  })
})
