import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import { eventBus } from '@/utils/eventBus'
import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import UserListScreen from './UserListScreen.vue'

describe('userListScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      h.actingAsAdmin()
    },
  })

  const renderComponent = async (users: User[] = []) => {
    if (users.length === 0) {
      users = h.factory('user', 6)
    }

    const fetchMock = h.mock(http, 'get').mockResolvedValue(users)

    h.render(UserListScreen, {
      global: {
        stubs: {
          Btn,
          BtnGroup,
          UserCard: h.stub('user-card'),
        },
      },
    })

    expect(fetchMock).toHaveBeenCalledWith('users')

    await h.tick(2)
  }

  it('displays a list of users', async () => {
    await renderComponent()

    expect(screen.getAllByTestId('user-card')).toHaveLength(6)
    expect(screen.queryByTestId('prospects-heading')).toBeNull()
  })

  it('displays a list of user prospects', async () => {
    const users = [...factory.states('prospect')('user', 2), ...h.factory('user', 3)]
    await renderComponent(users)

    expect(screen.getAllByTestId('user-card')).toHaveLength(5)
    screen.getByTestId('prospects-heading')
  })

  it('triggers create user modal', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Add' }))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_ADD_USER_FORM')
  })

  it('triggers invite user modal', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Invite' }))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_INVITE_USER_FORM')
  })
})
