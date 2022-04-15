import Component from '@/components/playlist/sidebar-item.vue'
import factory from '@/__tests__/factory'
import { shallow, mount } from '@/__tests__/adapter'

describe('components/playlist/sidebar-item', () => {
  let playlist: Playlist
  beforeEach(() => {
    playlist = factory<Playlist>('playlist', {
      id: 99,
      name: 'Foo'
    })
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('edits a playlist', async () => {
    const wrapper = mount(Component, {
      propsData: { playlist }
    })

    wrapper.dblclick('li.playlist')
    await wrapper.vm.$nextTick()
    expect(wrapper.has('[name=name]')).toBe(true)
  })

  it("doesn't allow editing Favorites item", async () => {
    const wrapper = shallow(Component, {
      propsData: {
        playlist: { name: 'Favorites' },
        type: 'favorites'
      }
    })

    wrapper.dblclick('li.favorites')
    await wrapper.vm.$nextTick()
    expect(wrapper.has('[name=name]')).toBe(false)
  })
})
