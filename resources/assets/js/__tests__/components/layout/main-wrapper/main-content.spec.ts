import Component from '@/components/layout/main-wrapper/MainContent.vue'
import { eventBus } from '@/utils'
import factory from '@/__tests__/factory'
import { shallow } from '@/__tests__/adapter'

describe('components/layout/main-wrapper/MainContent', () => {
  it('has a translucent image per song/album', () => {
    const wrapper = shallow(Component)
    const song = factory('song', {
      album: factory('album', {
        cover: 'http://foo/bar.jpg'
      })
    })
    eventBus.emit('SONG_STARTED', song)
    expect(wrapper).toMatchSnapshot()
  })
})
