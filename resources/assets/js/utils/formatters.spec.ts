import { describe, expect, it } from 'vite-plus/test'
import type { ServerValidationError } from './formatters'
import {
  br2nl,
  formatBytes,
  normalizeForComparison,
  parseValidationError,
  pluralize,
  secondsToHis,
  secondsToHumanReadable,
  slugToTitle,
} from './formatters'

describe('formatters utils', () => {
  it.each([
    [0, '00:00'],
    [59, '00:59'],
    [60, '01:00'],
    [125, '02:05'],
    [7547, '02:05:47'],
    [137241, '38:07:21'],
  ])('formats %d seconds to H:i:s', (seconds, formatted) => expect(secondsToHis(seconds)).toBe(formatted))

  it.each([
    [0, '0 sec'],
    [59, '59 sec'],
    [60, '1 min'],
    [125, '2 min 5 sec'],
    [7547, '2 hr 5 min'],
    [137241, '38 hr 7 min'],
  ])('formats %d seconds to human readable', (seconds, formatted) => {
    expect(secondsToHumanReadable(seconds)).toBe(formatted)
  })

  it('parses validation error', () => {
    const error: ServerValidationError = {
      message: 'The given data was invalid',
      errors: {
        email: ['The email has already been taken', 'The domain is blacklisted'],
        name: ['The name is required'],
      },
    }

    expect(parseValidationError(error)).toEqual([
      'The email has already been taken',
      'The domain is blacklisted',
      'The name is required',
    ])
  })

  it.each([
    ['foo<br>bar', 'foo\nbar'],
    ['foo<br/>bar', 'foo\nbar'],
    ['foo<br />bar', 'foo\nbar'],
    ['foo<br>bar<br/>baz', 'foo\nbar\nbaz'],
  ])('converts <br> tags in %s to line breaks', (input, output) => expect(br2nl(input)).toEqual(output))

  it.each([
    ['foo', 'Foo'],
    ['foo-bar', 'Foo Bar'],
    ['foo-bar--baz', 'Foo Bar Baz'],
    ['foo-bar--baz---', 'Foo Bar Baz'],
  ])('converts %s to the title counterpart', (slug, title) => expect(slugToTitle(slug)).toEqual(title))

  it.each([
    [1, 'cat', 'cat'],
    [2, 'cat', 'cats'],
    [0, 'cat', 'cats'],
  ])('pluralizes %d %s', (count, noun, plural) => expect(pluralize(count, noun)).toEqual(`${count} ${plural}`))

  it.each([
    [['foo'], 'cat', 'cat'],
    [['foo', 'bar'], 'cat', 'cats'],
    [[], 'cat', 'cats'],
  ])('pluralizes with array parameters', (arr, noun, plural) =>
    expect(pluralize(arr, noun)).toEqual(`${arr.length} ${plural}`),
  )

  it.each([
    [0, '0 B'],
    [512, '512 B'],
    [1024, '1.0 KB'],
    [1536, '1.5 KB'],
    [1048576, '1.0 MB'],
    [1073741824, '1.0 GB'],
    [524288000, '500.0 MB'],
  ])('formats %d bytes', (bytes, formatted) => expect(formatBytes(bytes)).toBe(formatted))

  it.each<[string, string]>([
    ['Hello World', 'hello world'],
    ['AN AMAZING SONG', 'an amazing song'],
    ['Xin chào Việt Nam', 'xin chao viet nam'],
    ['Über den Wölken', 'uber den wolken'],
    ['Café 你好', 'cafe 你好'],
    ['你好世界', '你好世界'],
    ['こんにちは世界', 'こんにちは世界'],
    ['안녕하세요 세계', '안녕하세요 세계'.normalize('NFKD')],
    ['مرحبا بالعالم', 'مرحبا بالعالم'],
    ['Hello, World!', 'hello world'],
    ['Fade - To Black', 'fade to black'],
    ['Song Title (Remastered)', 'song title remastered'],
    ['Song Title [Live]', 'song title live'],
    ['Mr. Brightside', 'mr brightside'],
    ["Don't Stop Me Now", 'dont stop me now'],
    ['Hello   World', 'hello world'],
  ])('normalizes "%s" for comparison', (input, expected) => {
    expect(normalizeForComparison(input)).toBe(expected)
  })
})
