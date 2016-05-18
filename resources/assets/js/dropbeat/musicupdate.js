import http from '../services/http';

import $ from 'jquery';

export default {

    state: {

        musicData: [],

    },

    SelectSongData(music) {

        var that = this;

        that.state.musicData = music;


    },

    Rdata() {

        return this.state.musicData;
    },

    update(data, successCb = null, errorCb = null) {
        http.post('dropbeat', data, successCb, errorCb );
    },

};
