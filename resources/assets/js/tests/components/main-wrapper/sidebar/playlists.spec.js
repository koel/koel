import Component from '@/components/main-wrapper/sidebar/playlists.vue'
import PlaylistItem from '@/components/main-wrapper/sidebar/playlist-item.vue'
import factory from '@/tests/factory'

describe('compopents/main-wrapper/main-content/sidebar/playlist', () => {
  it('renders properly', () => {
    const wrapper = mount(Component, { data: {
      playlistState: {
        playlists: factory('playlist', 5)
      }
    }})
    wrapper.findAll(PlaylistItem).should.have.lengthOf(6) // favorites + 5 playlists
  })
})
