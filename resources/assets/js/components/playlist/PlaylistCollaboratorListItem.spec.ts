import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlaylistCollaboratorListItem.vue'

describe('playlistCollaboratorListItem.vue', () => {
  const h = createHarness()

  const renderComponent = (props: {
    collaborator: PlaylistCollaborator
    removable: boolean
    manageable: boolean
    role: 'owner' | 'contributor'
  }) => {
    return h.render(Component, {
      props,
      global: {
        stubs: {
          UserAvatar: h.stub('UserAvatar'),
        },
      },
    })
  }

  it('does not show a badge when current user is not the collaborator', async () => {
    const currentUser = h.factory('user')
    h.actingAsUser(currentUser)
    renderComponent({
      collaborator: h.factory('playlist-collaborator', { id: currentUser.id + 1 }),
      removable: true,
      manageable: true,
      role: 'owner',
    })

    expect(screen.queryByTitle('This is you!')).toBeNull()
  })

  it('shows a badge when current user is the collaborator', async () => {
    const currentUser = h.factory('user')
    h.actingAsUser(currentUser)
    renderComponent({
      collaborator: h.factory('playlist-collaborator', {
        id: currentUser.id,
        name: currentUser.name,
        avatar: currentUser.avatar,
      }),
      removable: true,
      manageable: true,
      role: 'owner',
    })

    screen.getByTitle('This is you!')
  })

  it('shows the role', async () => {
    const collaborator = h.factory('playlist-collaborator')

    h.actingAsUser()
    renderComponent({
      collaborator,
      removable: true,
      manageable: true,
      role: 'owner',
    })

    screen.getByText('Owner')

    h.actingAsUser()
    renderComponent({
      collaborator,
      removable: true,
      manageable: true,
      role: 'contributor',
    })

    screen.getByText('Contributor')
  })

  it('emits the remove event when the remove button is clicked', async () => {
    const collaborator = h.factory('playlist-collaborator')
    h.actingAsUser()
    const { emitted } = renderComponent({
      collaborator,
      removable: true,
      manageable: true,
      role: 'owner',
    })

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    expect(emitted('remove')).toBeTruthy()
  })
})
