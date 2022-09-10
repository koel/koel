import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import { br2nl, parseValidationError, pluralize, secondsToHis, ServerValidationError, slugToTitle } from './formatters'

new class extends UnitTestCase {
  protected test () {
    it.each([
      [0, '00:00'],
      [59, '00:59'],
      [60, '01:00'],
      [125, '02:05'],
      [7547, '02:05:47'],
      [137241, '38:07:21']
    ])('formats %d seconds to H:i:s', (seconds, formatted) => expect(secondsToHis(seconds)).toBe(formatted))

    it('parses validation error', () => {
      const error: ServerValidationError = {
        message: 'The given data was invalid',
        errors: {
          email: [
            'The email has already been taken',
            'The domain is blacklisted'
          ],
          name: [
            'The name is required'
          ]
        }
      }

      expect(parseValidationError(error)).toEqual([
        'The email has already been taken',
        'The domain is blacklisted',
        'The name is required'
      ])
    })

    it.each([
      ['foo<br>bar', 'foo\nbar'],
      ['foo<br/>bar', 'foo\nbar'],
      ['foo<br />bar', 'foo\nbar'],
      ['foo<br>bar<br/>baz', 'foo\nbar\nbaz']
    ])('converts <br> tags in %s to line breaks', (input, output) => expect(br2nl(input)).toEqual(output))

    it.each([
      ['foo', 'Foo'],
      ['foo-bar', 'Foo Bar'],
      ['foo-bar--baz', 'Foo Bar Baz'],
      ['foo-bar--baz---', 'Foo Bar Baz']
    ])('converts %s to the title counterpart', (slug, title) => expect(slugToTitle(slug)).toEqual(title))

    it.each([
      [1, 'cat', 'cat'],
      [2, 'cat', 'cats'],
      [0, 'cat', 'cats']
    ])('pluralizes %d %s', (count, noun, plural) => expect(pluralize(count, noun)).toEqual(`${count} ${plural}`))

    it.each([
      [['foo'], 'cat', 'cat'],
      [['foo', 'bar'], 'cat', 'cats'],
      [[], 'cat', 'cats'],
    ])(
      'pluralizes with array parameters',
      (arr, noun, plural) => expect(pluralize(arr, noun)).toEqual(`${arr.length} ${plural}`)
    )
  }
}
