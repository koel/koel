import Artists from '@/components/main-wrapper/main-content/artists.vue'
import ArtistItem from '@/components/shared/artist-item.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/main-content/artists', () => {
  it('displays a list of artists', () => {
    const wrapper = shallow(Artists)
    wrapper.setData({
      artists: factory('artist', 5)
    })
    wrapper.findAll(ArtistItem).should.have.lengthOf(5)
  })
})
