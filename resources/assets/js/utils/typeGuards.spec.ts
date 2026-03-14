import { describe, expect, it } from 'vite-plus/test'
import { getPlayableCollectionContentType, isEpisode, isRadioStation, isSong } from './typeGuards'

describe('typeGuards', () => {
  it('identifies a song', () => {
    expect(isSong({ type: 'songs' } as Song)).toBe(true)
    expect(isSong({ type: 'episodes' } as unknown as Episode)).toBe(false)
  })

  it('identifies an episode', () => {
    expect(isEpisode({ type: 'episodes' } as Episode)).toBe(true)
    expect(isEpisode({ type: 'songs' } as unknown as Song)).toBe(false)
  })

  it('identifies a radio station', () => {
    expect(isRadioStation({ type: 'radio-stations' } as RadioStation)).toBe(true)
    expect(isRadioStation({ type: 'songs' } as unknown as Song)).toBe(false)
  })

  it('detects homogeneous song collection', () => {
    const songs = [{ type: 'songs' }, { type: 'songs' }] as Song[]
    expect(getPlayableCollectionContentType(songs)).toBe('songs')
  })

  it('detects homogeneous episode collection', () => {
    const episodes = [{ type: 'episodes' }, { type: 'episodes' }] as Episode[]
    expect(getPlayableCollectionContentType(episodes)).toBe('episodes')
  })

  it('detects mixed collection', () => {
    const mixed = [{ type: 'songs' }, { type: 'episodes' }] as Playable[]
    expect(getPlayableCollectionContentType(mixed)).toBe('mixed')
  })
})
