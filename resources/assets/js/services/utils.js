export default {
    /**
     * Convert a duration in seconds into H:i:s format.
     * If H is 0, it will be ommited.
     */
    secondsToHis(d) {
        d = parseInt(d);
        
        var s = d%60;

        if (s < 10) {
            s = '0' + s;
        }

        var i = Math.floor((d/60)%60);

        if (i < 10) {
            i = '0' + i;
        }

        var h = Math.floor(d/3600);

        if (h < 10) {
            h = '0' + h;
        }

        return (h === '00' ? '' : h + ':') + i + ':' + s;
    },

    /**
     * Quick object check - this is primarily used to tell
     * Objects from primitive values when we know the value
     * is a JSON-compliant type.
     *
     * Copied directly from Vue source
     *
     * @param {*} obj
     * @return {Boolean}
     */
    isObject(obj) {
         return obj !== null && typeof obj === 'object';
    },

    /**
     * Get from an object from a path string
     *
     * Poor mimick of Vue's core getPath
     *
     * @param {Object} obj
     * @param {String} path
     */
    getPath(obj, path) {
        var paths = path.split('.');

        for (var i = 0; i < paths.length; i++) {
            obj = obj[paths[i]];
        }

        return obj;
    },
};
