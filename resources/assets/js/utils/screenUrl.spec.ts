import { afterEach, describe, expect, it } from 'vite-plus/test'
import { screenUrl, toScreenPath } from './screenUrl'

describe('screenUrl', () => {
  afterEach(() => {
    window.KOEL.clean_urls = false
  })

  it('points into the hash without clean URLs', () => {
    expect(screenUrl('/songs/1')).toBe(`${window.KOEL.base_url}#/songs/1`)
  })

  it('points at a plain path with clean URLs', () => {
    window.KOEL.clean_urls = true

    expect(screenUrl('songs/1')).toBe(`${window.KOEL.base_url}songs/1`)
  })

  it.each(['#/albums', '/#/albums', 'albums', '/albums'])('reads %s as the Albums screen path', path => {
    expect(toScreenPath(path)).toBe('/albums')
  })
})
