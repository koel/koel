import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import { eventBus } from '@/utils'
import { fireEvent } from '@testing-library/vue'
import UserBadge from './UserBadge.vue'

new class extends UnitTestCase {
  private renderComponent () {
    return this.actingAs(factory<User>('user', {
      name: 'John Doe'
    })).render(UserBadge)
  }

  protected test () {
    it('renders', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('logs out', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      const { getByTestId } = this.renderComponent()

      await fireEvent.click(getByTestId('btn-logout'))

      expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
    })
  }
}
