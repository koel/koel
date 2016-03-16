import ls from 'local-storage';

export default {
    get(key, defaultVal = null) {
        let val = ls(key);

        return val ? val : defaultVal;
    },

    set(key, val) {
        return ls(key, val);
    },

    remove(key) {
        return ls.remove(key);
    },
};
