import Component from '@/components/screens/RecentlyPlayedScreen.vue'
import SongList from '@/components/song/SongList.vue'
import factory from '@/__tests__/factory'
import { recentlyPlayedStore } from '@/stores'
import { eventBus } from '@/utils'
import { mock } from '@/__tests__/__helpers__'
import { mount, shallow } from '@/__tests__/adapter'

describe('components/screens/recently-played', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', async () => {
    const wrapper = mount(Component, {
      data: () => ({
        state: {
          songs: factory('song', 5)
        }
      })
    })

    await wrapper.vm.$nextTick()
    expect(wrapper.has(SongList)).toBe(true)
  })

  it('fetch and populate content on demand', () => {
    shallow(Component)
    const m = mock(recentlyPlayedStore, 'fetchAll')
    eventBus.emit('LOAD_MAIN_CONTENT', 'RecentlyPlayed')
    expect(m).toHaveBeenCalled()
  })
})
