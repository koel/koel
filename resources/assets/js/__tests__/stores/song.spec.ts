// @ts-nocheck
import { songStore, albumStore, artistStore } from '@/stores'
import data from '@/__tests__/blobs/data'

describe('stores/song', () => {
  beforeEach(() => {
    artistStore.init(data.artists)
    albumStore.init(data.albums)
    songStore.init(data.songs)
    songStore.initInteractions(data.interactions)
  })

  it('gathers all songs', () => {
    expect(songStore.state.songs).toHaveLength(14)
  })

  it('converts length to formatted lengths', () => {
    expect(songStore.state.songs[0].fmtLength).toBe('04:19')
  })

  it('sets albums', () => {
    expect(songStore.state.songs[0].album.id).toBe(1193)
  })

  it('returns all songs', () => {
    expect(songStore.state.songs).toHaveLength(14)
  })

  it('gets a song by ID', () => {
    expect(songStore.byId('e6d3977f3ffa147801ca5d1fdf6fa55e').title).toBe('Like a rolling stone')
  })

  it('gets multiple songs by IDs', () => {
    const songs = songStore.byIds(['e6d3977f3ffa147801ca5d1fdf6fa55e', 'aa16bbef6a9710eb9a0f41ecc534fad5'])
    expect(songs[0].title).toBe('Like a rolling stone')
    expect(songs[1].title).toBe("Knockin' on heaven's door")
  })

  it('sets interaction status', () => {
    const song = songStore.byId('cb7edeac1f097143e65b1b2cde102482')
    expect(song.liked).toBe(true)
    expect(song.playCount).toBe(3)
  })

  it('guesses a song', () => {
    expect(songStore.guess('i swear', albumStore.byId(1193))!.id).toBe('39189f4545f9d5671fb3dc964f0080a0')
  })
})
