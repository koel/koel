import AlbumInfo from '@/components/main-wrapper/extra/album-info.vue'
import TrackListItem from '@/components/shared/track-list-item.vue'
import album from '@/tests/blobs/album'
import _ from 'lodash'

describe('components/main-wrapper/extra/album-info', () => {
  it('displays the info as a sidebar by default', () => {
    const wrapper = shallow(AlbumInfo, {
      propsData: {
        album
      }
    })
    expect(wrapper.findAll('#albumInfo.sidebar')).toHaveLength(1)
    expect(wrapper.findAll('#albumInfo.full')).toHaveLength(0)
  })

  it('can display the info in full mode', () => {
    const wrapper = shallow(AlbumInfo, {
      propsData: {
        album,
        mode: 'full'
      }
    })
    expect(wrapper.findAll('#albumInfo.sidebar')).toHaveLength(0)
    expect(wrapper.findAll('#albumInfo.full')).toHaveLength(1)
  })

  it('triggers showing full wiki', () => {
    const wrapper = shallow(AlbumInfo, {
      propsData: {
        album
      }
    })
    wrapper.find('.wiki button.more').trigger('click')
    expect(wrapper.html()).toContain(album.info.wiki.full)
  })

  it('lists the correct number of tracks', () => {
    const wrapper = mount(AlbumInfo, {
      propsData: {
        album
      }
    })
    expect(wrapper.findAll(TrackListItem)).toHaveLength(2)
  })

  it('displays a message if the album has no info', () => {
    const albumWithNoInfo = _.clone(album)
    albumWithNoInfo.info = null
    const wrapper = mount(AlbumInfo, {
      propsData: {
        album: albumWithNoInfo
      }
    })
    expect(wrapper.html()).toContain('No album information found.')
  })
})
