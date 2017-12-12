import Component from '@/components/main-wrapper/main-content/favorites.vue'
import SongList from '@/components/shared/song-list.vue'
import SongListControls from '@/components/shared/song-list-controls.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/main-content/favorites', () => {
  it('displays the song list if there are favorites', () => {
    const wrapper = shallow(Component)
    wrapper.setData({
      state: {
        songs: factory('song', 5)
      }
    })
    wrapper.contains(SongList).should.be.true
    wrapper.contains(SongListControls).should.be.true
    wrapper.findAll('div.none').should.have.lengthOf(0)
  })

  it('displays a fallback message if there are no favorites', () => {
    const wrapper = shallow(Component)
    wrapper.setData({
      state: {
        songs: []
      }
    })
    wrapper.findAll('div.none').should.have.lengthOf(1)
  })

  it('allows downloading', () => {
    const wrapper = shallow(Component)
    wrapper.setData({
      state: {
        songs: factory('song', 5)
      },
      sharedState: { allowDownload: true }
    })
    const spy = sinon.spy()
    wrapper.download = spy
    wrapper.find('a.download').trigger('click')
    spy.should.have.been.called
  })
})
