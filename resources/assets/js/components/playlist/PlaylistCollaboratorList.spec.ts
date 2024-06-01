import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playlistCollaborationService } from '@/services'
import Component from './PlaylistCollaboratorList.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', async () => {
      const playlist = factory('playlist', {
        is_collaborative: true
      })

      const fetchMock = this.mock(playlistCollaborationService, 'fetchCollaborators').mockResolvedValue(
        factory('playlist-collaborator', 5)
      )

      const { html } = await this.be().renderComponent(playlist)
      expect(fetchMock).toHaveBeenCalledWith(playlist)
      expect(html()).toMatchSnapshot()
    })
  }

  private async renderComponent (playlist: Playlist) {
    const rendered = this.render(Component, {
      props: {
        playlist
      },
      global: {
        stubs: {
          ListItem: this.stub('ListItem')
        }
      }
    })

    await this.tick(2)

    return rendered
  }
}
