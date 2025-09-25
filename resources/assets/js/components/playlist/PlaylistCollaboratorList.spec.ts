import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import Component from './PlaylistCollaboratorList.vue'

describe('playlistCollaboratorList.vue', () => {
  const h = createHarness()

  const renderComponent = async (playlist: Playlist) => {
    const rendered = h.render(Component, {
      props: {
        playlist,
      },
      global: {
        stubs: {
          ListItem: h.stub('ListItem'),
        },
      },
    })

    await h.tick(2)

    return rendered
  }

  it('renders', async () => {
    const playlist = h.factory('playlist', {
      is_collaborative: true,
    })

    const fetchMock = h.mock(playlistCollaborationService, 'fetchCollaborators').mockResolvedValue(
      h.factory('playlist-collaborator', 5),
    )

    h.actingAsUser()
    const { html } = await renderComponent(playlist)
    expect(fetchMock).toHaveBeenCalledWith(playlist)
    expect(html()).toMatchSnapshot()
  })
})
