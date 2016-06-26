import { each, filter, isObject, isNumber, get, includes } from 'lodash';

export function orderBy (arr, sortKey, reverse) {
  if (!sortKey) {
    return arr;
  }

  const order = (reverse && reverse < 0) ? -1 : 1;

  function compareRecordsByKey(a, b, key) {
    let aKey = isObject(a) ? get(a, key) : a;
    let bKey = isObject(b) ? get(b, key) : b;

    if (isNumber(aKey) && isNumber(bKey)) {
      return aKey === bKey ? 0 : aKey > bKey;
    }

    aKey = aKey === undefined ? aKey : `${aKey}`.toLowerCase();
    bKey = bKey === undefined ? bKey : `${bKey}`.toLowerCase();

    return aKey === bKey ? 0 : aKey > bKey;
  }

  // sort on a copy to avoid mutating original array
  return arr.slice().sort((a, b) => {
    if (sortKey.constructor === Array) {
      let diff = 0;
      for (let i = 0; i < sortKey.length; i++) {
        diff = compareRecordsByKey(a, b, sortKey[i]);
        if (diff !== 0) {
          break;
        }
      }

      return diff === 0 ? 0 : diff === true ? order : -order;
    }

    a = isObject(a) ? get(a, sortKey) : a;
    b = isObject(b) ? get(b, sortKey) : b;

    if (isNumber(a) && isNumber(b)) {
      return a === b ? 0 : a > b ? order : -order;
    }

    a = a === undefined ? a : a.toLowerCase();
    b = b === undefined ? b : b.toLowerCase();

    return a === b ? 0 : a > b ? order : -order;
  });
};

export function limitBy (arr, n, offset = 0) {
  return arr.slice(offset, offset + n);
};

export function filterBy (arr, search, ...keys) {
  if (!search) {
    return arr;
  }

  // cast to lowercase string
  search = (`${search}`).toLowerCase();

  const res = [];

  each(arr, item => {
    each(keys, key => {
      if (`${get(item, key)}`.toLowerCase().indexOf(search) !== -1) {
        res.push(item);
        return false;
      }
    })
  })

  return res;
};

export function pluralize () {
  if (!arguments[0] || arguments[0] > 1) {
    return `${arguments[0]} ${arguments[1]}s`;
  }

  return `${arguments[0]} ${arguments[1]}`;
};
