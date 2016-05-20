import $ from 'jquery';
import _ from 'lodash';
// import searchBox from './searchBox';
// import searchList from './searchList';
// import recommendList from './recommendList';
import feature from './feature';
import playermanager from './playermanager';

export default {

    state: {
        youtubeApiReady: false,
        soundManagerReady: false,
        dropbeatReady: false
    },

    initialize() {

        playermanager.init();

    },


    api(name) {
        var scheme = 'http',
            baseApiHost = 'api.dropbeat.net',
            uri = '/dropbeat/api/',
            version = 'v1',
            url = scheme + '://' + baseApiHost + uri + version + '/';


            return url + name + '/';

        // return {
        //     searchUrl: endpoint('search'),
        //     recommendUrl: endpoint('recom'),
        //     playlistUrl: endpoint('playlist'),
        //     initialPlaylistUrl: endpoint('playlist/initial'),
        //     resolveUrl: egenUrl: endpoint('generate')
        // };
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

    onYouTubeIframeAPIReady() {
        this.state.youtubeApiReady = true;
    },
};
