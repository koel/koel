import Component from '@/components/artist/ArtistContextMenu.vue'
import factory from '@/__tests__/factory'
import { downloadService, playbackService } from '@/services'
import { commonStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { mount, shallow } from '@/__tests__/adapter'

describe('components/artist/ContextMenuBase', () => {
  let artist: Artist

  beforeEach(() => {
    artist = factory<Artist>('artist')
    // @ts-ignore
    commonStore.state = { allowDownload: true }
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('plays all', () => {
    const wrapper = shallow(Component, { propsData: { artist } })
    const m = mock(playbackService, 'playAllByArtist')

    wrapper.click('[data-testid=play]')
    expect(m).toHaveBeenCalledWith(artist)
  })

  it('shuffles', () => {
    const wrapper = shallow(Component, { propsData: { artist } })
    const m = mock(playbackService, 'playAllByArtist')

    wrapper.click('[data-testid=shuffle]')
    expect(m).toHaveBeenCalledWith(artist, true)
  })

  it('downloads', async () => {
    const wrapper = mount(Component, { propsData: { artist } })
    await wrapper.vm.$nextTick()
    await (wrapper.vm as any).open(0, 0)
    const m = mock(downloadService, 'fromArtist')

    wrapper.click('[data-testid=download]')
    expect(m).toHaveBeenCalledWith(artist)
  })

  it('does not have a download item if not downloadable', () => {
    // @ts-ignore
    commonStore.state = { allowDownload: false }
    const wrapper = shallow(Component, { propsData: { artist } })
    expect(wrapper.has('[data-testid=download]')).toBe(false)
  })
})
