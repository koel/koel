import type { Mock } from 'vite-plus/test'
import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useContextMenu } from '@/composables/useContextMenu'
import Component from './UserCard.vue'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import UserContextMenu from '@/components/user/UserContextMenu.vue'

vi.mock('@/composables/useContextMenu')

describe('userCard.vue', () => {
  const h = createHarness({
    beforeEach: () => (useContextMenu().openContextMenu as Mock).mockClear(),
  })

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

  it('shows the profile link for the current user', () => {
    const user = h.factory.states('current')('user') as CurrentUser
    h.actingAsUser(user)
    renderComponent(user)

    screen.getByTitle('This is you!')
    expect(screen.getByRole('link', { name: 'Your Profile' }).getAttribute('href')).toBe('/#/profile')
  })

  it('does not show profile link for other users', () => {
    h.actingAsUser(h.factory.states('current')('user') as CurrentUser)
    renderComponent(h.factory('user'))

    expect(screen.queryByRole('link', { name: 'Your Profile' })).toBeNull()
  })

  it('requests the context menu on right-click', async () => {
    const { openContextMenu } = useContextMenu()
    const { user } = renderComponent()

    await h.trigger(screen.getByTestId('user-card'), 'contextMenu')
    await assertOpenContextMenu(openContextMenu as Mock, UserContextMenu, { user })
  })

  it('requests the context menu via the More Actions button', async () => {
    const { openContextMenu } = useContextMenu()
    const { user } = renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
    await assertOpenContextMenu(openContextMenu as Mock, UserContextMenu, { user })
  })

  it('does not show the More Actions button for the current user', () => {
    const user = h.factory.states('current')('user') as CurrentUser
    h.actingAsUser(user)
    renderComponent(user)

    expect(screen.queryByRole('button', { name: 'More Actions' })).toBeNull()
  })
})
