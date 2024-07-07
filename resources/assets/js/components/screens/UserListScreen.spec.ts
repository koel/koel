import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { screen } from '@testing-library/vue'
import { http } from '@/services'
import { eventBus } from '@/utils'
import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import UserListScreen from './UserListScreen.vue'

new class extends UnitTestCase {
  protected beforeEach (cb?: Closure) {
    super.beforeEach(cb);

    this.beAdmin()
  }

  protected test () {
    it('displays a list of users', async () => {
      await this.renderComponent()

      expect(screen.getAllByTestId('user-card')).toHaveLength(6)
      expect(screen.queryByTestId('prospects-heading')).toBeNull()
    })

    it('displays a list of user prospects', async () => {
      const users = [...factory.states('prospect')('user', 2), ...factory('user', 3)]
      await this.renderComponent(users)

      expect(screen.getAllByTestId('user-card')).toHaveLength(5)
      screen.getByTestId('prospects-heading')
    })

    it('triggers create user modal', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      await this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Add' }))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_ADD_USER_FORM')
    })

    it('triggers invite user modal', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      await this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Invite' }))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_INVITE_USER_FORM')
    })
  }

  private async renderComponent (users: User[] = []) {
    if (users.length === 0) {
      users = factory('user', 6)
    }

    const fetchMock = this.mock(http, 'get').mockResolvedValue(users)

    this.render(UserListScreen, {
      global: {
        stubs: {
          Btn,
          BtnGroup,
          UserCard: this.stub('user-card')
        }
      }
    })

    expect(fetchMock).toHaveBeenCalledWith('users')

    await this.tick(2)
  }
}
