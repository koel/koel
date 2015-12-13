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
};
