import Lyrics from '@/components/main-wrapper/extra/lyrics.vue'
import song from '@/tests/blobs/song'

describe('components/main-wrapper/extra/lyrics', () => {
  it('displays lyrics if the song has lyrics', () => {
    const wrapper = shallow(Lyrics, {
      propsData: { song }
    })
    wrapper.html().should.contain(song.lyrics)
  })

  it('displays a fallback message if the song has no lyrics', () => {
    const songWithNoLyrics = _.clone(song)
    songWithNoLyrics.lyrics = null
    const wrapper = shallow(Lyrics, {
      propsData: {
        song: songWithNoLyrics
      }
    })
    wrapper.html().should.contain('No lyrics found. Are you not listening to Bach?')
  })
})
