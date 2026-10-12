import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import Component from './PlaylistCollaboratorList.vue'

describe('playlistCollaboratorList.vue', () => {
  const h = createHarness()

  const ListItemStub = defineComponent({
    props: ['collaborator', 'role'],
    template: '<li data-testid="collaborator" :data-id="collaborator.id" :data-role="role" />',
  })

  it('lists the current user first, then the owner, then the others', async () => {
    const owner = h.factory('playlist-collaborator').make()
    const currentUser = h.factory('playlist-collaborator').make()
    const other = h.factory('playlist-collaborator').make()
    const playlist = h.factory('playlist').make({ owner_id: owner.id, is_collaborative: true })

    const fetchMock = h
      .mock(playlistCollaborationService, 'fetchCollaborators')
      .mockResolvedValue([other, owner, currentUser])

    h.actingAsUser(h.factory('user').state('current').make({ id: currentUser.id }) as CurrentUser)

    h.render(Component, {
      props: { playlist },
      global: {
        stubs: {
          ListItem: ListItemStub,
        },
      },
    })

    await h.tick(2)

    expect(fetchMock).toHaveBeenCalledWith(playlist)
    expect(screen.getAllByTestId('collaborator').map(item => [item.dataset.id, item.dataset.role])).toEqual([
      [currentUser.id, 'contributor'],
      [owner.id, 'owner'],
      [other.id, 'contributor'],
    ])
  })
})
