import Component from '@/components/shared/song-list-controls.vue'
import factory from '@/tests/factory'

describe('components/shared/song-list-controls', () => {
  it('allows shuffling all if less than 2 songs are selected', () => {
    let wrapper = shallow(Component, { propsData: {
      selectedSongs: []
    }})
    wrapper.find('.btn-shuffle-all').trigger('click')
    wrapper.emitted().shuffleAll.should.be.ok

    wrapper = shallow(Component, { propsData: {
      selectedSongs: [factory('song')]
    }})
    wrapper.find('.btn-shuffle-all').trigger('click')
    wrapper.emitted().shuffleAll.should.be.ok
  })

  it('allows shuffling selected if more than 1 song are selected', () => {
    const wrapper = shallow(Component, { propsData: {
      selectedSongs: factory('song', 3)
    }})
    wrapper.find('.btn-shuffle-selected').trigger('click')
    wrapper.emitted().shuffleSelected.should.be.ok
  })

  it('displays the "Add To" menu', () => {
    shallow(Component, { propsData: {
      selectedSongs: factory('song', 3)
    }}).contains('.btn-add-to').should.be.true
  })

  it('allows clearing queue', () => {
    const wrapper = shallow(Component, {
      data: {
        fullConfig: {
          clearQueue: true 
        }
      }
    })
    wrapper.find('.btn-clear-queue').trigger('click')
    wrapper.emitted().clearQueue.should.be.ok
  })

  it('allows deleting current playlist', () => {
    const wrapper = shallow(Component, {
      data: {
        fullConfig: {
          deletePlaylist: true 
        }
      }
    })
    wrapper.find('.btn-delete-playlist').trigger('click')
    wrapper.emitted().deletePlaylist.should.be.ok
  })
})
