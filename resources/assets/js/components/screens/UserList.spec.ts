import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { screen } from '@testing-library/vue'
import { http } from '@/services'
import { eventBus } from '@/utils'
import Btn from '@/components/ui/Btn.vue'
import BtnGroup from '@/components/ui/BtnGroup.vue'
import UserListScreen from './UserListScreen.vue'

new class extends UnitTestCase {
  private async renderComponent () {
    const fetchMock = this.mock(http, 'get').mockResolvedValue(factory<User>('user', 6))

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

  protected test () {
    it('displays a list of users', async () => {
      await this.renderComponent()
      expect(screen.getAllByTestId('user-card')).toHaveLength(6)
    })

    it('triggers create user modal', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      await this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Add' }))

      expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_ADD_USER_FORM')
    })
  }
}
