import $ from 'jquery';
import _ from 'lodash';

import feature from './feature';

export default {

    state: {
        youtubeApiReady: false,
        soundManagerReady: false,
        dropbeatReady: false
    },


    api(name) {
        var scheme = 'http',
            baseApiHost = 'api.dropbeat.net',
            uri = '/dropbeat/api/',
            version = 'v1',
            url = scheme + '://' + baseApiHost + uri + version + '/';


            return url + name + '/';

    },

    compatibility() {
        var navigator = window.navigator,
            chrome = navigator.userAgent.match(/(Chrome)/g),
            firefox = navigator.userAgent.match(/(Firefox)/g),
            safari = (navigator.userAgent.match(/(Safari)/g)) && !chrome,
            ie = !chrome && !firefox && !safari;

        return {
            isExplorer: ie,
            isSafari: safari
        };
    },

    escapes(title) {
            return title.replace(/"/g, "'");
    },
};
