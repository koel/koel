import Component from '@/components/main-wrapper/main-content/index.vue'
import { event } from '@/utils'
import factory from '@/tests/factory'

describe('components/main-wrapper/main-content/index', () => {
  it('has a translucent image per song/album', () => {
    const wrapper = shallow(Component)
    const song = factory('song', {
      album: factory('album', {
        cover: 'http://foo/bar.jpg'
      })
    })
    event.emit('song:played', song)
    wrapper.vm.albumCover.should.equal(song.album.cover)
  })
})
