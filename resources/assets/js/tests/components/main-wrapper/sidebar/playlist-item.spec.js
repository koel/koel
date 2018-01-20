import Component from '@/components/main-wrapper/sidebar/playlist-item.vue'
import factory from '@/tests/factory'
import { playlistStore } from '@/stores'

describe('component/main-wrapper/sidebar/playlist-item', () => {
  let playlist
  beforeEach(() => {
    playlist = factory('playlist', {
      id: 99,
      name: 'Foo'
    })
  })

  it('renders a playlist menu item', () => {
    shallow(Component, {
      propsData: { playlist }
    }).find('a[href="#!/playlist/99"]').text().should.equal('Foo')
  })

  it('renders the Favorites menu item', () => {
    shallow(Component, {
      propsData: {
        playlist: {
          name: 'Favorites'
        },
        type: 'favorites'
      }
    }).find('a[href="#!/favorites"]').text().should.equal('Favorites')
  })

  it('edits a playlist', () => {
    const updateStub = sinon.stub(playlistStore, 'update')
    const wrapper = shallow(Component, {
      propsData: { playlist }
    })
    wrapper.dblclick('li.playlist')
    wrapper.blur('input[type=text]')
    updateStub.calledWith(playlist).should.be.true
  })

  it("doesn't allow editing Favorites item", () => {
    const wrapper = shallow(Component, {
      propsData: {
        playlist: { name: 'Favorites' },
        type: 'favorites'
      }
    })
    wrapper.dblclick('li.favorites')
    wrapper.has('input[type=text]').should.be.false
  })
})
