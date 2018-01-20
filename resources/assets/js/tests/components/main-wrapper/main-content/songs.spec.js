import Component from '@/components/main-wrapper/main-content/songs.vue'
import SongList from '@/components/shared/song-list.vue'
import factory from '@/tests/factory'
import { songStore } from '@/stores'

describe('components/main-wrapper/main-content/settings', () => {
  it('renders properly', () => {
    songStore.all = factory('song', 10)
    const wrapper = shallow(Component)
    wrapper.find('h1.heading').text().should.contain('All Songs')
    wrapper.has(SongList).should.be.true
  })
})
