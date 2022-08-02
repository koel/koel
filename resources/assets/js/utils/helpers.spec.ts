import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it, vi } from 'vitest'
import { arrayify, limitBy, use } from './helpers'

new class extends UnitTestCase {
  protected test () {
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
      [['foo', 'bar'], ['foo', 'bar']]
    ])('turns the parameter into an array', (input, output) => expect(arrayify(input)).toEqual(output))

    it.each([
      [2, 0, ['a', 'b']],
      [2, 1, ['b', 'c']],
      [1, 0, ['a']],
      [1, 2, ['c']],
      [0, 0, []],
      [0, 1, []]
    ])('takes %d elements from %d position', (count, position, result) => {
      expect(limitBy(['a', 'b', 'c', 'd'], count, position)).toEqual(result)
    })
  }
}
