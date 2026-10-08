import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PlaylistCollaborationModal.vue'

describe('playlistCollaborationModal.vue', () => {
  const h = createHarness()

  const renderComponent = (playlist: Playlist) =>
    h.render(Component, {
      props: { playlist },
      global: {
        stubs: {
          InviteCollaborators: h.stub('invite-collaborators'),
          CollaboratorList: h.stub('collaborator-list'),
        },
      },
    })

  it('lets the owner invite collaborators', () => {
    const playlist = h.factory('playlist').make()
    h.actingAsUser(h.factory('user').state('current').make({ id: playlist.owner_id }) as CurrentUser)

    renderComponent(playlist)

    screen.getByTestId('invite-collaborators')
  })

  it('does not let a collaborator invite others', () => {
    h.actingAsUser()

    renderComponent(h.factory('playlist').make())

    expect(screen.queryByTestId('invite-collaborators')).toBeNull()
  })
})
