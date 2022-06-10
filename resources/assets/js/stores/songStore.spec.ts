import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import { songStore } from '@/stores/songStore'

new class extends UnitTestCase {
  protected test () {
    it('gets a song by ID', () => {
      expect(songStore.byId('e6d3977f3ffa147801ca5d1fdf6fa55e')!.title).toBe('Like a rolling stone')
    })

    it('gets multiple songs by IDs', () => {
      const songs = songStore.byIds(['e6d3977f3ffa147801ca5d1fdf6fa55e', 'aa16bbef6a9710eb9a0f41ecc534fad5'])
      expect(songs[0].title).toBe('Like a rolling stone')
      expect(songs[1].title).toBe('Knockin\' on heaven\'s door')
    })

    it('sets interaction status', () => {
      const song = songStore.byId('cb7edeac1f097143e65b1b2cde102482')!
      expect(song.liked).toBe(true)
      expect(song.play_count).toBe(3)
    })

    it('guesses a song', () => {
      throw 'Unimplemented'
    })
  }
}
