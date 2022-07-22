import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { fireEvent } from '@testing-library/vue'
import router from '@/router'
import { eventBus } from '@/utils'
import UserCard from './UserCard.vue'

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
      const { getByTitle, getByText } = this.actingAs(user).renderComponent(user)

      getByTitle('This is you!')
      getByText('Your Profile')
    })

    it('edits user', async () => {
      const user = factory<User>('user')
      const emitMock = this.mock(eventBus, 'emit')
      const { getByText } = this.renderComponent(user)

      await fireEvent.click(getByText('Edit'))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_USER_FORM', user)
    })

    it('redirects to Profile screen if edit current user', async () => {
      const mock = this.mock(router, 'go')
      const user = factory<User>('user')
      const { getByText } = this.actingAs(user).renderComponent(user)

      await fireEvent.click(getByText('Your Profile'))

      expect(mock).toHaveBeenCalledWith('profile')
    })
  }
}
