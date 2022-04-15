import Component from '@/components/screens/favorites.vue'
import SongList from '@/components/song/list.vue'
import SongListControls from '@/components/song/list-controls.vue'
import { download } from '@/services'
import factory from '@/__tests__/factory'
import { mock } from '@/__tests__/__helpers__'
import { mount, shallow } from '@/__tests__/adapter'

describe('components/screens/favorites', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('displays the song list if there are favorites', async () => {
    const wrapper = mount(Component, {
      data: () => ({
        state: {
          songs: factory('song', 5)
        }
      })
    })

    await wrapper.vm.$nextTick()
    expect(wrapper.hasAll(SongList, SongListControls)).toBe(true)
    expect(wrapper.findAll('div.none')).toHaveLength(0)
  })

  it('displays a fallback message if there are no favorites', async () => {
    const wrapper = mount(Component, {
      data: () => ({
        state: {
          songs: []
        }
      })
    })

    await wrapper.vm.$nextTick()
    expect(wrapper.has('[data-test=screen-placeholder]')).toBe(true)
  })

  it('allows downloading', () => {
    const m = mock(download, 'fromFavorites')

    shallow(Component, {
      data: () => ({
        state: {
          songs: factory('song', 5),
        },
        sharedState: { allowDownload: true },
        meta: {
          songCount: 5,
          totalLength: '12:34'
        }
      })
    }).click('a.download')

    expect(m).toHaveBeenCalled()
  })
})
