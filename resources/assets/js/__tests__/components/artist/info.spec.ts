import Component from '@/components/artist/info.vue'
import ArtistThumbnail from '@/components/ui/album-artist-thumbnail.vue'
import factory from '@/__tests__/factory'
import { shallow, mount } from '@/__tests__/adapter'

describe('components/artist/info', () => {
  it('displays the info as a sidebar by default', () => {
    const wrapper = shallow(Component, {
      propsData: {
        artist: factory('artist')
      }
    })
    expect(wrapper.findAll('.artist-info.sidebar')).toHaveLength(1)
    expect(wrapper.findAll('.artist-info.full')).toHaveLength(0)
  })

  it('can display the info in full mode', () => {
    const wrapper = shallow(Component, {
      propsData: {
        artist: factory('artist'),
        mode: 'full'
      }
    })
    expect(wrapper.findAll('.artist-info.sidebar')).toHaveLength(0)
    expect(wrapper.findAll('.artist-info.full')).toHaveLength(1)
  })

  it('triggers showing full bio', () => {
    const artist = factory<Artist>('artist')
    const wrapper = shallow(Component, {
      propsData: { artist }
    })
    wrapper.click('.bio button.more')
    expect(wrapper.html()).toMatch(artist.info!.bio!.full)
  })

  it('shows the artist thumbnail', async () => {
    const artist = factory('artist')
    const wrapper = mount(Component, {
      propsData: { artist }
    })
    await wrapper.vm.$nextTick()
    expect(wrapper.has(ArtistThumbnail)).toBe(true)
  })
})
