import { afterEach, describe, expect, it } from 'vite-plus/test'
import { clientUrl, toClientPath } from './clientUrl'

describe('clientUrl', () => {
  afterEach(() => {
    window.KOEL.clean_urls = false
  })

  it('points into the hash without clean URLs', () => {
    expect(clientUrl('/songs/1')).toBe(`${window.KOEL.base_url}#/songs/1`)
  })

  it('points at a plain path with clean URLs', () => {
    window.KOEL.clean_urls = true

    expect(clientUrl('songs/1')).toBe(`${window.KOEL.base_url}songs/1`)
  })

  it.each(['#/albums', '/#/albums', 'albums', '/albums'])('reads %s as the Albums screen path', path => {
    expect(toClientPath(path)).toBe('/albums')
  })
})
