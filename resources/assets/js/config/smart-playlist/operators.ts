export const is: SmartPlaylistOperator = {
  operator: 'is',
  label: 'is'
}

export const isNot: SmartPlaylistOperator = {
  operator: 'isNot',
  label: 'is not'
}

export const contains: SmartPlaylistOperator = {
  operator: 'contains',
  label: 'contains'
}

export const notContain: SmartPlaylistOperator = {
  operator: 'notContain',
  label: 'does not contain'
}

export const isBetween: SmartPlaylistOperator = {
  operator: 'isBetween',
  label: 'is between',
  inputs: 2
}

export const isGreaterThan: SmartPlaylistOperator = {
  operator: 'isGreaterThan',
  label: 'is greater than'
}

export const isLessThan: SmartPlaylistOperator = {
  operator: 'isLessThan',
  label: 'is less than'
}

export const beginsWith: SmartPlaylistOperator = {
  operator: 'beginsWith',
  label: 'begins with'
}

export const endsWith: SmartPlaylistOperator = {
  operator: 'endsWith',
  label: 'ends with'
}

export const inLast: SmartPlaylistOperator = {
  operator: 'inLast',
  label: 'in the last',
  type: 'number', // overriding
  unit: 'days'
}

export const notInLast: SmartPlaylistOperator = {
  operator: 'notInLast',
  label: 'not in the last',
  type: 'number', // overriding
  unit: 'days'
}

export default [
  is, isNot, contains, notContain, isBetween, isGreaterThan, isLessThan, beginsWith, endsWith, inLast, notInLast
]
