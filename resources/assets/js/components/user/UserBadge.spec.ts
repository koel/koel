import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import { eventBus } from '@/utils'
import { screen } from '@testing-library/vue'
import UserBadge from './UserBadge.vue'

new class extends UnitTestCase {
  private renderComponent () {
    return this.be(factory<User>('user', {
      name: 'John Doe'
    })).render(UserBadge)
  }

  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('logs out', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Log out' }))

      expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
    })
  }
}
