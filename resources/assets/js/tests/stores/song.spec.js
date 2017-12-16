import { songStore, albumStore, artistStore, preferenceStore } from '../../stores'
import data from '../blobs/data'

const { songs, artists, albums, interactions } = data

describe('stores/song', () => {
  beforeEach(() => {
    artistStore.init(artists)
    albumStore.init(albums)
    songStore.init(songs)
  })

  describe('#init', () => {
    it('correctly gathers all songs', () => {
      songStore.state.songs.length.should.equal(14)
    })

    it ('coverts lengths to formatted lengths', () => {
      songStore.state.songs[0].fmtLength.should.be.a.string
    })

    it('correctly sets albums', () => {
      songStore.state.songs[0].album.id.should.equal(1193)
    })
  })

  describe('#all', () => {
    it('correctly returns all songs', () => {
      songStore.all.length.should.equal(14)
    })
  })

  describe('#byId', () => {
    it('correctly gets a song by ID', () => {
      songStore.byId('e6d3977f3ffa147801ca5d1fdf6fa55e').title.should.equal('Like a rolling stone')
    })
  })

  describe('#byIds', () => {
    it('correctly gets multiple songs by IDs', () => {
      const songs = songStore.byIds(['e6d3977f3ffa147801ca5d1fdf6fa55e', 'aa16bbef6a9710eb9a0f41ecc534fad5'])
      songs[0].title.should.equal('Like a rolling stone')
      songs[1].title.should.equal("Knockin' on heaven's door")
    })
  })

  describe('#initInteractions', () => {
    beforeEach(() => songStore.initInteractions(interactions))

    it('correctly sets interaction status', () => {
      const song = songStore.byId('cb7edeac1f097143e65b1b2cde102482')
      song.liked.should.be.true
      song.playCount.should.equal(3)
    })
  })

  describe('#addRecentlyPlayed', () => {
    it('correctly adds a recently played song', () => {
      songStore.addRecentlyPlayed(songStore.byId('cb7edeac1f097143e65b1b2cde102482'))
      songStore.recentlyPlayed[0].id.should.equal('cb7edeac1f097143e65b1b2cde102482')
      preferenceStore.get('recent-songs')[0].should.equal('cb7edeac1f097143e65b1b2cde102482')
    })

    it('correctly gathers the songs from local storage', () => {
      songStore.gatherRecentlyPlayedFromLocalStorage()[0].id.should.equal('cb7edeac1f097143e65b1b2cde102482')
    })
  })

  describe('#guess', () => {
    it('correcty guesses a song', () => {
      songStore.guess('i swear', albumStore.byId(1193)).id.should.equal('39189f4545f9d5671fb3dc964f0080a0')
    })
  })
})
