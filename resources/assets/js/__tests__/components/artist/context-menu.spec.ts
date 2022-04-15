import Component from '@/components/artist/context-menu.vue'
import factory from '@/__tests__/factory'
import { playback, download } from '@/services'
import { sharedStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { shallow, mount } from '@/__tests__/adapter'

describe('components/artist/context-menu', () => {
  let artist: Artist

  beforeEach(() => {
    artist = factory<Artist>('artist')
    // @ts-ignore
    sharedStore.state = { allowDownload: true }
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('plays all', () => {
    const wrapper = shallow(Component, { propsData: { artist } })
    const m = mock(playback, 'playAllByArtist')

    wrapper.click('[data-test=play]')
    expect(m).toHaveBeenCalledWith(artist)
  })

  it('shuffles', () => {
    const wrapper = shallow(Component, { propsData: { artist } })
    const m = mock(playback, 'playAllByArtist')

    wrapper.click('[data-test=shuffle]')
    expect(m).toHaveBeenCalledWith(artist, true)
  })

  it('downloads', async () => {
    const wrapper = mount(Component, { propsData: { artist } })
    await wrapper.vm.$nextTick()
    await (wrapper.vm as any).open(0, 0)
    const m = mock(download, 'fromArtist')

    wrapper.click('[data-test=download]')
    expect(m).toHaveBeenCalledWith(artist)
  })

  it('does not have a download item if not downloadable', () => {
    // @ts-ignore
    sharedStore.state = { allowDownload: false }
    const wrapper = shallow(Component, { propsData: { artist } })
    expect(wrapper.has('[data-test=download]')).toBe(false)
  })
})
