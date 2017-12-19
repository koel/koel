import Component from '@/components/main-wrapper/sidebar/index.vue'
import Playlists from '@/components/main-wrapper/sidebar/playlists.vue'
import { sharedStore } from '@/stores'
import factory from '@/tests/factory'

describe('compoponents/main-wrapper/sidebar/index', () => {
  it('renders properly', () => {
    const wrapper = shallow(Component)
    ;['home', 'queue', 'songs', 'albums', 'artists'].forEach(item => {
      wrapper.contains(`.menu a.${item}`).should.be.true
    })
    wrapper.contains(Playlists).should.be.true
  })

  it('displays YouTube menu item if using YouTube', () => {
    sharedStore.state.useYouTube = true
    shallow(Component).contains('a.youtube').should.be.true
  })

  it('displays management menu items for admin', () => {
    const wrapper = shallow(Component, { data: {
      userState: {
        current: factory('user', { is_admin: true })
      }
    }})
    ;['settings', 'users'].forEach(item => {
      wrapper.contains(`.menu a.${item}`).should.be.true
    })
  })

  it('displays new version info', () => {
    sharedStore.state.currentVersion = 'v0.0.0'
    sharedStore.state.latestVersion = 'v0.0.1'
    const wrapper = shallow(Component, { data: {
      userState: {
        current: factory('user', { is_admin: true })
      }
    }})
    wrapper.contains('a.new-ver').should.be.true
    wrapper.find('a.new-ver').text().should.contain('Koel version v0.0.1 is available!')
  })
})
