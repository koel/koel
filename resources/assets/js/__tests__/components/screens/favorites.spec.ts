import Component from '@/components/screens/FavoritesScreen.vue'
import SongList from '@/components/song/SongList.vue'
import SongListControls from '@/components/songSongListControls.vue'
import { downloadService } from '@/services'
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
    expect(wrapper.has('[data-testid=screen-empty-state]')).toBe(true)
  })

  it('allows downloading', () => {
    const m = mock(downloadService, 'fromFavorites')

    shallow(Component, {
      data: () => ({
        state: {
          songs: factory('song', 5)
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
