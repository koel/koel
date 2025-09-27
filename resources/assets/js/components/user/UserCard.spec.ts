import type { Mock } from 'vitest'
import { describe, expect, it, vi } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useContextMenu } from '@/composables/useContextMenu'
import Component from './UserCard.vue'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import UserContextMenu from '@/components/user/UserContextMenu.vue'

describe('userCard.vue', () => {
  const h = createHarness()

  const renderComponent = (user?: User) => {
    user = user ?? h.factory('user')

    const rendered = h.render(Component, {
      props: {
        user,
      },
    })

    return {
      ...rendered,
      user,
    }
  }

  it('has different behaviors for current user', () => {
    const user = h.factory.states('current')('user') as CurrentUser
    h.actingAsUser(user)
    renderComponent(user)

    screen.getByTitle('This is you!')
    expect(screen.getByText('Your Profile').getAttribute('href')).toBe('/#/profile')
    expect(screen.queryByRole('button', { name: 'More Actions' })).toBeNull()
  })

  it('requests the context menu', async () => {
    vi.mock('@/composables/useContextMenu')
    const { openContextMenu } = useContextMenu()
    const { user } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
    await assertOpenContextMenu(openContextMenu as Mock, UserContextMenu, { user })
  })
})
