import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import AddUserForm from '@/components/user/AddUserForm.vue'
import InviteUserForm from '@/components/user/InviteUserForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

import UserListScreen from './UserListScreen.vue'

describe('userListScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      openModalMock.mockClear()
      Element.prototype.scrollTo = vi.fn()
      h.actingAsAdmin()
    },
  })

  const renderComponent = async (users: User[] = []) => {
    if (users.length === 0) {
      users = h.factory('user').make(6)
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

  it('displays a list of users without filters when nobody is invited', async () => {
    await renderComponent()

    expect(screen.getAllByTestId('user-card')).toHaveLength(6)
    expect(screen.queryByRole('radiogroup')).toBeNull()
  })

  it('filters between users and invited prospects', async () => {
    const users = [...factory('user').state('prospect').make(2), ...h.factory('user').make(3)]
    await renderComponent(users)

    expect(screen.getAllByTestId('user-card')).toHaveLength(3)
    expect(screen.getByTestId('user-filter-count-invited').textContent?.trim()).toBe('2')

    await h.user.click(screen.getByTestId('user-filter-invited'))

    expect(screen.getAllByTestId('user-card')).toHaveLength(2)
  })

  it('triggers create user modal', async () => {
    await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Add' }))

    await assertOpenModal(openModalMock, AddUserForm)
  })

  it('triggers invite user modal', async () => {
    await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Invite' }))

    await assertOpenModal(openModalMock, InviteUserForm)
  })
})
