import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './UserCard.vue'

describe('userCard.vue', () => {
  const h = createHarness()

  const renderComponent = (user: User) => {
    return h.render(Component, {
      props: {
        user,
      },
    })
  }

  it('has different behaviors for current user', () => {
    const user = h.factory('user')
    h.actingAsUser(user)
    renderComponent(user)

    screen.getByTitle('This is you!')
    expect(screen.getByText('Your Profile').getAttribute('href')).toBe('/#/profile')
    expect(screen.queryByRole('button', { name: 'More Actions' })).toBeNull()
  })

  it('requests the context menu', async () => {
    const user = h.factory('user')
    const emitMock = h.mock(eventBus, 'emit')
    renderComponent(user)

    await h.user.click(screen.getByRole('button', { name: 'More Actions' }))

    expect(emitMock).toHaveBeenCalledWith('USER_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), user)
  })
})
