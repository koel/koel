import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { arrayify, flattenParams, gravatar, limitBy, use } from './helpers'

describe('helpers utils', () => {
  describe('gravatar()', () => {
    // Must match the URL built by the gravatar() PHP helper, which is what the server serves as an avatar.
    const EMAIL_SHA256 = 'efbe2fad818a477cc2eef45f6be5fd0a1111aead627c3529562f17f0375d4523'

    afterEach(() => (window.KOEL.gravatar = { url: 'https://www.gravatar.com/avatar', default: 'robohash' }))

    it('identifies the email by its SHA-256 hash', async () => {
      expect(await gravatar('koel@example.com')).toBe(
        `https://www.gravatar.com/avatar/${EMAIL_SHA256}?s=192&d=robohash`,
      )
    })

    it('normalizes the email before hashing', async () => {
      expect(await gravatar('  KOEL@Example.com  ')).toBe(await gravatar('koel@example.com'))
    })

    it('honors the configured URL and default', async () => {
      window.KOEL.gravatar = { url: 'https://gravatar.example.com/avatar', default: 'identicon' }

      expect(await gravatar('koel@example.com', 64)).toBe(
        `https://gravatar.example.com/avatar/${EMAIL_SHA256}?s=64&d=identicon`,
      )
    })
  })

  it('use() triggers a closure with a defined value', () => {
    const mock = vi.fn()
    use('foo', mock)
    expect(mock).toHaveBeenCalledWith('foo')
  })

  it('use() does not trigger a closure with an undefined value', () => {
    const mock = vi.fn()
    use(undefined, mock)
    expect(mock).not.toHaveBeenCalled()
  })

  it.each([
    ['foo', ['foo']],
    [
      ['foo', 'bar'],
      ['foo', 'bar'],
    ],
  ])('turns the parameter into an array', (input, output) => expect(arrayify(input)).toEqual(output))

  it.each([
    [2, 0, ['a', 'b']],
    [2, 1, ['b', 'c']],
    [1, 0, ['a']],
    [1, 2, ['c']],
    [0, 0, []],
    [0, 1, []],
  ])('takes %d elements from %d position', (count, position, result) => {
    expect(limitBy(['a', 'b', 'c', 'd'], count, position)).toEqual(result)
  })

  describe('flattenParams', () => {
    it('flattens scalar values', () => {
      expect(flattenParams({ type: 'album', id: 42 })).toEqual({ type: 'album', id: '42' })
    })

    it('flattens array values with indexed keys', () => {
      expect(flattenParams({ ids: ['a', 'b', 'c'] })).toEqual({
        'ids[0]': 'a',
        'ids[1]': 'b',
        'ids[2]': 'c',
      })
    })

    it('handles mixed scalar and array values', () => {
      expect(flattenParams({ type: 'songs', ids: [1, 2] })).toEqual({
        type: 'songs',
        'ids[0]': '1',
        'ids[1]': '2',
      })
    })

    it('skips null and undefined values', () => {
      expect(flattenParams({ type: 'favorites', id: null, extra: undefined })).toEqual({ type: 'favorites' })
    })
  })
})
