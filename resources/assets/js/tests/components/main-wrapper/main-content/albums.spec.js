import Albums from '@/components/main-wrapper/main-content/albums.vue'
import AlbumItem from '@/components/shared/album-item.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/main-content/albums', () => {
  it('displays a list of albums', () => {
    const wrapper = shallow(Albums, {
      propsData: {
        albums: factory('album', 5)
      }
    })
    wrapper.findAll(AlbumItem).should.have.lengthOf(5)
  })
})
