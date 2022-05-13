import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import UserCard from './UserCard.vue'
import Btn from '@/components/ui/Btn.vue'
import { fireEvent } from '@testing-library/vue'
import router from '@/router'

new class extends ComponentTestCase {
  private renderComponent (user: User) {
    return this.render(UserCard, {
      props: {
        user
      },
      global: {
        stubs: {
          Btn
        }
      }
    })
  }

  protected test () {
    it('has different behaviors for current user', () => {
      const user = factory<User>('user')
      const { getByTitle, getByText } = this.actingAs(user).renderComponent(user)

      getByTitle('This is you!')
      getByText('Update Profile')
    })

    it('edits user', async () => {
      const user = factory<User>('user')
      const { emitted, getByText } = this.renderComponent(user)

      await fireEvent.click(getByText('Edit'))

      expect(emitted().editUser[0]).toEqual([user])
    })

    it('redirects to Profile screen if edit current user', async () => {
      const mock = this.mock(router, 'go')
      const user = factory<User>('user')
      const { getByText } = this.actingAs(user).renderComponent(user)

      await fireEvent.click(getByText('Update Profile'))

      expect(mock).toHaveBeenCalledWith('profile')
    })

    // the rest should be handled by E2E
  }
}
