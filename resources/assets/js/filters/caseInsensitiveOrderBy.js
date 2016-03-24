import Vue from 'vue';
import { isNumber } from 'lodash';

/**
 * Modified version of orderBy that is case insensitive
 *
 * @source https://github.com/vuejs/vue/blob/dev/src/filters/array-filters.js
 */
export function caseInsensitiveOrderBy (arr, sortKey, reverse) {
    if (!sortKey) {
        return arr;
    }

    let order = (reverse && reverse < 0) ? -1 : 1;

    function compareRecordsByKey(a, b, key) {
        let aKey = Vue.util.isObject(a) ? Vue.parsers.path.getPath(a, key) : a;
        let bKey = Vue.util.isObject(b) ? Vue.parsers.path.getPath(b, key) : b;

        if (isNumber(aKey) && isNumber(bKey)) {
            return aKey === bKey ? 0 : aKey > bKey;
        }

        aKey = aKey === undefined ? aKey : aKey.toLowerCase();
        bKey = bKey === undefined ? bKey : bKey.toLowerCase();

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

        a = Vue.util.isObject(a) ? Vue.parsers.path.getPath(a, sortKey) : a;
        b = Vue.util.isObject(b) ? Vue.parsers.path.getPath(b, sortKey) : b;

        if (isNumber(a) && isNumber(b)) {
            return a === b ? 0 : a > b ? order : -order;
        }

        a = a === undefined ? a : a.toLowerCase();
        b = b === undefined ? b : b.toLowerCase();

        return a === b ? 0 : a > b ? order : -order;
    });
}
