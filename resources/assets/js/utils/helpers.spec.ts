import { describe, expect, it, vi } from 'vitest'
import { arrayify, flattenParams, limitBy, use } from './helpers'

describe('helpers utils', () => {
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
