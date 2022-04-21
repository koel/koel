import Component from '@/components/songSongListControls.vue'
import factory from '@/__tests__/factory'
import { take } from 'lodash'
import { shallow, mount } from '@/__tests__/adapter'

describe('components/song/list-controls', () => {
  it('renders properly', () => {
    expect(shallow(Component)).toMatchSnapshot()
  })

  it.each<[Song[], number]>([[factory<Song>('song', 5), 0], [factory<Song>('song', 5), 1]])(
    'allows shuffling all if less than 2 songs are selected',
    async (songs, selectedSongCount) => {
      const selectedSongs = take(songs, selectedSongCount)
      const wrapper = mount(Component, { propsData: { songs, selectedSongs } })

      await wrapper.vm.$nextTick()
      expect(wrapper.click('.btn-shuffle-all').hasEmitted('playAll', true)).toBe(true)
    })

  it('allows shuffling selected if more than 1 song are selected', () => {
    const songs = factory<Song>('song', 5)

    expect(shallow(Component, {
      propsData: {
        songs,
        selectedSongs: take(songs, 2)
      }
    }).click('.btn-shuffle-selected').hasEmitted('playSelected', true)).toBe(true)
  })

  it('displays the "Add To" menu', () => {
    const songs = factory<Song>('song', 5)

    expect(shallow(Component, {
      propsData: {
        songs,
        selectedSongs: take(songs, 2)
      }
    }).has('.btn-add-to')).toBe(true)
  })

  it('allows clearing queue', () => {
    expect(shallow(Component, {
      propsData: {
        config: {
          clearQueue: true
        }
      }
    }).click('.btn-clear-queue').hasEmitted('clearQueue')).toBe(true)
  })

  it('allows deleting current playlist', () => {
    expect(shallow(Component, {
      propsData: {
        config: {
          deletePlaylist: true
        }
      }
    }).click('.btn-delete-playlist').hasEmitted('deletePlaylist')).toBe(true)
  })
})
