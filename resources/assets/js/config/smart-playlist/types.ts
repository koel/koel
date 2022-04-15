import {
  is,
  isNot,
  contains,
  notContain,
  beginsWith,
  endsWith,
  isBetween,
  isGreaterThan,
  isLessThan,
  inLast,
  notInLast
} from '@/config/smart-playlist/operators'

const types: SmartPlaylistTypes = {
  text: [is, isNot, contains, notContain, beginsWith, endsWith],
  number: [is, isNot, isGreaterThan, isLessThan, isBetween],
  date: [is, isNot, inLast, notInLast, isBetween]
}

export default types
