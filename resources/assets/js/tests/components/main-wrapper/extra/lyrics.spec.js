import Lyrics from '@/components/main-wrapper/extra/lyrics.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/extra/lyrics', () => {
  it('displays lyrics if the song has lyrics', () => {
    const song = factory('song')
    const wrapper = shallow(Lyrics, {
      propsData: { song }
    })
    wrapper.html().should.contain(song.lyrics)
  })

  it('displays a fallback message if the song has no lyrics', () => {
    const wrapper = shallow(Lyrics, {
      propsData: {
        song: factory('song', { lyrics: '' })
      }
    })
    wrapper.html().should.contain('No lyrics found. Are you not listening to Bach?')
  })
})
