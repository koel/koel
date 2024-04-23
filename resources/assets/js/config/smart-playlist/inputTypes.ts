import {
  beginsWith,
  contains,
  endsWith,
  inLast,
  is,
  isBetween,
  isGreaterThan,
  isLessThan,
  isNot,
  notContain,
  notInLast
} from '@/config/smart-playlist/operators'

const inputTypes: SmartPlaylistInputTypes = {
  text: [is, isNot, contains, notContain, beginsWith, endsWith],
  number: [is, isNot, isGreaterThan, isLessThan, isBetween],
  date: [is, isNot, inLast, notInLast, isBetween]
}

export default inputTypes
