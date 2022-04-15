/* eslint @typescript-eslint/no-unused-vars: 0 */
import _, { Cancelable } from 'lodash'

_.orderBy = jest.fn(<T>(collection: T[]): T[] => collection)

_.shuffle = jest.fn(<T>(collection: T[]): T[] => collection)

_.throttle = jest.fn((fn: Function, wait: number): any => fn)

_.sample = jest.fn(<T>(collection: T[]): T | undefined => {
  return collection.length ? collection[0] : undefined
})

module.exports = _
