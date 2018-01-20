import Component from '@/components/shared/song-list-controls.vue'
import factory from '@/tests/factory'

describe('components/shared/song-list-controls', () => {
  it('allows shuffling all if less than 2 songs are selected', () => {
    shallow(Component, { propsData: {
      selectedSongs: []
    }}).click('.btn-shuffle-all').hasEmitted('shuffleAll').should.be.true

    shallow(Component, { propsData: {
      selectedSongs: [factory('song')]
    }}).click('.btn-shuffle-all').hasEmitted('shuffleAll').should.be.true
  })

  it('allows shuffling selected if more than 1 song are selected', () => {
    shallow(Component, { propsData: {
      selectedSongs: factory('song', 3)
    }}).click('.btn-shuffle-selected').hasEmitted('shuffleSelected').should.be.true
  })

  it('displays the "Add To" menu', () => {
    shallow(Component, { propsData: {
      selectedSongs: factory('song', 3)
    }}).has('.btn-add-to').should.be.true
  })

  it('allows clearing queue', () => {
    shallow(Component, {
      data: {
        fullConfig: {
          clearQueue: true 
        }
      }
    }).click('.btn-clear-queue').hasEmitted('clearQueue').should.be.true
  })

  it('allows deleting current playlist', () => {
    shallow(Component, {
      data: {
        fullConfig: {
          deletePlaylist: true 
        }
      }
    }).click('.btn-delete-playlist').hasEmitted('deletePlaylist').should.be.true
  })
})
