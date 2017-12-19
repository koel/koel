import Albums from '@/components/main-wrapper/main-content/albums.vue'
import AlbumItem from '@/components/shared/album-item.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/main-content/albums', () => {
  it('displays a list of albums', () => {
    shallow(Albums, { data: {
      albums: factory('album', 5)
    }}).findAll(AlbumItem).should.have.lengthOf(5)
  })
})
