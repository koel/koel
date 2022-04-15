import Component from '@/components/album/context-menu.vue'
import factory from '@/__tests__/factory'
import { playback, download } from '@/services'
import { sharedStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { shallow, mount } from '@/__tests__/adapter'

describe('components/album/context-menu', () => {
  let album: Album

  beforeEach(() => {
    album = factory<Album>('album')
    // @ts-ignore
    sharedStore.state = { allowDownload: true }
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('plays all', () => {
    const wrapper = shallow(Component, { propsData: { album } })
    const m = mock(playback, 'playAllInAlbum')

    wrapper.click('[data-test=play]')
    expect(m).toHaveBeenCalledWith(album)
  })

  it('shuffles', () => {
    const wrapper = shallow(Component, { propsData: { album } })
    const m = mock(playback, 'playAllInAlbum')

    wrapper.click('[data-test=shuffle]')
    expect(m).toHaveBeenCalledWith(album, true)
  })

  it('downloads', async () => {
    const wrapper = mount(Component, { propsData: { album } })
    await wrapper.vm.$nextTick()
    await (wrapper.vm as any).open(0, 0)
    const m = mock(download, 'fromAlbum')

    wrapper.click('[data-test=download]')
    expect(m).toHaveBeenCalledWith(album)
  })

  it('does not have a download item if not downloadable', () => {
    // @ts-ignore
    sharedStore.state = { allowDownload: false }
    const wrapper = shallow(Component, { propsData: { album } })
    expect(wrapper.has('[data-test=download]')).toBe(false)
  })
})
