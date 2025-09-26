import { describe, expect, it } from 'vitest'
import { DialogBoxStub } from '@/__tests__/stubs'
import { userStore } from '@/stores/userStore'
import { screen, waitFor } from '@testing-library/vue'
import factory from '@/__tests__/factory'
import { invitationService } from '@/services/invitationService'
import { createHarness } from '@/__tests__/TestHarness'
import { acl } from '@/services/acl'
import Component from './UserContextMenu.vue'

describe('userContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (user?: User, editable = true, deletable = true) => {
    user = user || h.factory('user')

    const permissionMock = h.mock(acl, 'checkResourcePermission').mockImplementation((_, __, action) => {
      if (action === 'edit') {
        return editable
      }
      if (action === 'delete') {
        return deletable
      }
    })

    const rendered = h.render(Component, {
      props: {
        user,
      },
    })

    await waitFor(() => {
      screen.getByRole('listitem')
      expect(permissionMock).toHaveBeenCalledTimes(user.is_prospect ? 1 : 2)
    })

    return {
      ...rendered,
      user,
    }
  }

  it('deletes user if confirmed', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(true)
    const { user } = await renderComponent()
    const destroyMock = h.mock(userStore, 'destroy')

    await h.user.click(screen.getByText('Delete'))

    expect(destroyMock).toHaveBeenCalledWith(user)
  })

  it('does not delete user if not confirmed', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
    await renderComponent()
    const destroyMock = h.mock(userStore, 'destroy')

    await h.user.click(screen.getByText('Delete'))

    expect(destroyMock).not.toHaveBeenCalled()
  })

  it('revokes invite for prospects', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(true)
    const prospect = factory.states('prospect')('user')
    await renderComponent(prospect)
    const revokeMock = h.mock(invitationService, 'revoke')

    await h.user.click(screen.getByText('Revoke Invitation'))

    expect(revokeMock).toHaveBeenCalledWith(prospect)
  })

  it('does not revoke invite for prospects if not confirmed', async () => {
    h.mock(DialogBoxStub.value, 'confirm').mockResolvedValue(false)
    await renderComponent(factory.states('prospect')('user'))
    await h.user.click(screen.getByText('Revoke Invitation'))
    const revokeMock = h.mock(invitationService, 'revoke')

    await h.user.click(screen.getByText('Revoke Invitation'))

    expect(revokeMock).not.toHaveBeenCalled()
  })

  it('respects the permissions', async () => {
    await renderComponent(undefined, false, false)
    expect(screen.queryByText('Edit')).toBeNull()
    expect(screen.queryByText('Delete')).toBeNull()
  })
})
