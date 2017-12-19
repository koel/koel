import Lyrics from '@/components/main-wrapper/extra/lyrics.vue'
import factory from '@/tests/factory'

describe('components/main-wrapper/extra/lyrics', () => {
  it('displays lyrics if the song has lyrics', () => {
    const song = factory('song')
    shallow(Lyrics, {
      propsData: { song }
    }).html().should.contain(song.lyrics)
  })

  it('displays a fallback message if the song has no lyrics', () => {
    shallow(Lyrics, {
      propsData: {
        song: factory('song', { lyrics: '' })
      }
    }).html().should.contain('No lyrics found. Are you not listening to Bach?')
  })
})
