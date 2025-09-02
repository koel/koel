import { ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { MessageToasterStub } from '@/__tests__/stubs'
import { ModalContextKey } from '@/symbols'
import { userStore } from '@/stores/userStore'
import Component from './EditUserForm.vue'

describe('editUserForm.vue', () => {
  const h = createHarness()

  it('edits a user', async () => {
    const updateMock = h.mock(userStore, 'update')
    const alertMock = h.mock(MessageToasterStub.value, 'success')

    const user = ref(h.factory('user', { name: 'John Doe' }))

    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ user }),
        },
      },
    })

    await h.type(screen.getByRole('textbox', { name: 'Name' }), 'Jane Doe')
    await h.type(screen.getByTitle('Password'), 'new-password-duck')
    await h.user.click(screen.getByRole('button', { name: 'Update' }))

    await waitFor(() => {
      expect(updateMock).toHaveBeenCalledWith(user.value, {
        name: 'Jane Doe',
        email: user.value.email,
        is_admin: user.value.is_admin,
        password: 'new-password-duck',
      })

      expect(alertMock).toHaveBeenCalledWith('User profile updated.')
    })
  })
})
