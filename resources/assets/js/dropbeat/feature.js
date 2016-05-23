import Vue from 'vue';
import dataList from './data-list.vue';

import dropbeat from './dropbeat';
import music from './music';
import musicUpdate from './musicupdate';
import utils from '../services/utils';
import playback from '../services/playback';

import $ from 'jquery';
import _ from 'lodash';

export default {
    app: null,

    state: {
        searching: false,
        keyword: null,
        searchInput: "#search-input",
        searchButton: "#search-button",
        searchResultTemplate: "#tmpl-search-results",
        searchResultSection: ".search-result-section",
        musicContainer: ".a-addable-music",
        playMusicBtn: ".play-music",
        addToPlayListBtn: ".add-to-playlist",
        results: [],

    },


    init(app) {
        this.app = app;
        var that = this;

        $(that.state.searchButton).click(function() {
            that.onSubmit($(that.state.searchInput).val());
        });

        $(that.state.searchInput).keydown(function (event) {
// Handles Keydown of `Enter key`
            if (event.keyCode === 13) {
                that.onSubmit($(that.state.searchInput).val());
            }
        });

        that.delegateTrigger();
    },

    onSubmit(keyword) {
        var that = this,
            context = this.state;
        var searchUrl = dropbeat.api('search');

        keyword = encodeURIComponent(keyword);
        context.searching = true;
        context.keyword = keyword;


        $.ajax({
            url: searchUrl,
            data: decodeURIComponent($.param({
                'keyword': keyword,
                'type': 'jsonp'
            })),
            dataType: 'jsonp',
            jsonp: 'callback',
            success: function (data) {
                that.searchCallback(data);
            }
        });
    },

    searchCallback(data) {
        this.state.searching = false;
        this.updateView(data.tracks);
    },

    updateView(resp){
        var that = this;

        if (!resp) {
            return;
        }
         that.resultEscape(resp);

        this.state.results = resp;
    },

    resultEscape(resp){
        var i;

        for (i = 0; i < resp.length; i += 1){
            resp[i].title = dropbeat.escapes(resp[i].title);
        }
    },

    delegateTrigger() {
        var that = this;

        $(that.state.searchResultSection).on(
            "click",
            that.state.addToPlayListBtn,
            function() {
                var self = this,
                    $musicContainer =
                        $(self).
                            parents(that.state.musicContainer),
                    musicData = {
                        id: $musicContainer.data("musicId"),
                        title: $musicContainer.data("musicTitle"),
                        type: $musicContainer.data("musicType")
                    };

                musicUpdate.update(musicData,() => {
                    // Re-init the app.
                    var self = this;
                    $(self).parents(that.app.$root.init());

                }, error => {
                    var msg = 'Unknown error.';

                    if (error.status === 422) {
                        msg = utils.parseValidationError(error.data)[0];
                    }

                    $(self).parents(that.parents.$root.showOverlay(`Error: ${msg}`, 'error', true));
                });

                dataList.send = true;


            }
        );

        $(that.state.searchResultSection).on(
            "click",
            that.state.playMusicBtn,
            function() {
                var self = this,
                    $musicContainer =
                        $(self).
                            parents(that.state.musicContainer),
                    musicData = {
                        id: $musicContainer.data("musicId"),
                        title: $musicContainer.data("musicTitle"),
                        type: $musicContainer.data("musicType")
                    };

                playback.Splay(
                    new music.Music({
                        'id': musicData.id,
                        'title': musicData.title,
                        'type': musicData.type
                    })
                );
            }
        );
    },


};
