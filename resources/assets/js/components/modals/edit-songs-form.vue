<template>
    <div id="editSongsOverlay" v-if="shown">
        <sound-bar v-if="loading"></sound-bar>
        <form v-else @submit.prevent="submit">
            <header>
                <img :src="inSameAlbum ? songs[0].album.cover : '/public/img/covers/unknown-album.png'" width="96" height="96">
                <hgroup class="meta">
                    <h1 :class="{ mixed: !editSingle }">{{ displayedTitle }}</h1>
                    <h2 :class="{ mixed: !bySameArtist &&  !formData.artistName }">
                        {{ bySameArtist || formData.artistName ? formData.artistName : 'Mixed Artists' }}
                    </h2>
                    <h2 :class="{ mixed: !inSameAlbum && !formData.albumName }">
                        {{ inSameAlbum || formData.albumName ? formData.albumName : 'Mixed Albums' }}
                    </h2>
                </hgroup>
            </header>

            <div>
                <div class="tabs tabs-white">
                    <div class="header clear">
                        <a @click.prevent="currentView = 'details'"
                            :class="{ active: currentView === 'details' }">Details</a>
                        <a @click.prevent="currentView = 'lyrics'" v-show="editSingle"
                            :class="{ active: currentView === 'lyrics' }">Lyrics</a>
                    </div>

                    <div class="panes">
                        <div v-show="currentView === 'details'">
                            <div class="form-row" v-show="editSingle">
                                <label>Title</label>
                                <input type="text" v-model="formData.title">
                            </div>
                            <div class="form-row">
                                <label>Artist</label>
                                <typeahead
                                    :items="artistState.artists"
                                    :options="artistTypeaheadOptions"
                                    :value.sync="formData.artistName"></typeahead>
                            </div>
                            <div class="form-row">
                                <label>Album</label>
                                <typeahead
                                    :items="albumState.albums"
                                    :options="albumTypeaheadOptions"
                                    :value.sync="formData.albumName"></typeahead>
                            </div>
                        </div>
                        <div v-show="currentView === 'lyrics' && editSingle">
                            <div class="form-row">
                                <textarea v-model="formData.lyrics"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer>
                <input type="submit" value="Update" />
                <a @click.prevent="close" class="btn btn-white">Cancel</a>
            </footer>
        </form>
    </div>
</template>

<script>
    import _ from 'lodash';
    import $ from 'jquery';

    import artistStore from '../../stores/artist';
    import albumStore from '../../stores/album';
    import songStore from '../../stores/song';

    import soundBar from '../shared/sound-bar.vue';
    import typeahead from '../shared/typeahead.vue';

    export default {
        components: { soundBar, typeahead },

        data() {
            return {
                shown: false,
                songs: [],
                currentView: '',
                loading: false,

                artistState: artistStore.state,
                artistTypeaheadOptions: {
                    displayKey: 'name',
                    filterKey: 'name',
                },

                albumState: albumStore.state,
                albumTypeaheadOptions: {
                    displayKey: 'name',
                    filterKey: 'name',
                },

                /**
                 * In order not to mess up the original songs, we manually assign and manipulate
                 * their attributes.
                 *
                 * @type {Object}
                 */
                formData: {
                    title: '',
                    albumName: '',
                    artistName: '',
                    lyrics: '',
                },
            };
        },

        computed: {
            /**
             * Determine if we're editing but one song.
             *
             * @return {boolean}
             */
            editSingle() {
                return this.songs.length === 1;
            },

            /**
             * Determine if all songs we're editing are by the same artist.
             *
             * @return {boolean}
             */
            bySameArtist() {
                return _.every(this.songs, song => {
                    return song.album.artist.id === this.songs[0].album.artist.id;
                });
            },

            /**
             * Determine if all songs we're editing are from the same album.
             *
             * @return {boolean}
             */
            inSameAlbum() {
                return _.every(this.songs, song => {
                    return song.album.id === this.songs[0].album.id;
                });
            },

            /**
             * The song title to be displayed.
             *
             * @return {string}
             */
            displayedTitle() {
                return this.editSingle ? this.formData.title : `${this.songs.length} songs selected`;
            },

            /**
             * The album name to be displayed.
             *
             * @return {string}
             */
            displayedAlbum() {
                if (this.editSingle) {
                    return this.formData.albumName;
                } else {
                    return this.formData.albumName ? this.formData.albumName : 'Mixed Albums';
                }
            },

            /**
             * The artist name to be displayed.
             *
             * @return {string}
             */
            displayedArtist() {
                if (this.editSingle) {
                    return this.formData.artistName;
                } else {
                    return this.formData.artistName ? this.formData.artistName : 'Mixed Artists';
                }
            },
        },

        methods: {
            open(songs) {
                this.shown = true;
                this.songs = songs;
                this.currentView = 'details';

                if (this.editSingle) {
                    this.formData.title = this.songs[0].title;
                    this.formData.albumName = this.songs[0].album.name;
                    this.formData.artistName = this.songs[0].album.artist.name;

                    // If we're editing only one song and the song's info (including lyrics)
                    // hasn't been loaded, load it now.
                    if (!this.songs[0].infoRetrieved) {
                        this.loading = true;

                        songStore.getInfo(this.songs[0], () => {
                            this.loading = false;
                            this.formData.lyrics = this.songs[0].lyrics;
                        });
                    } else {
                        this.formData.lyrics = this.songs[0].lyrics;
                    }
                } else {
                    this.formData.albumName = this.inSameAlbum ? this.songs[0].album.name : '';
                    this.formData.artistName = this.bySameArtist ? this.songs[0].album.artist.name : '';
                    this.loading = false;
                }
            },

            /**
             * Close the modal.
             */
            close() {
                // Todo: Confirm.
                this.shown = false;
            },

            /**
             * Submit the form.
             */
            submit() {
                this.loading = true;

                songStore.update(this.songs, this.formData, () => {
                    this.loading = false;
                    this.close();
                }, () => {
                    this.loading = false;
                });
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #editSongsOverlay {
        z-index: 9999;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, .7);
        overflow: auto;

        @include vertical-center();

        $borderRadius: 5px;

        form {
            position: relative;
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: $borderRadius;
            color: #333;

            .form-row::first-child {
                margin-top: 0;
            }

            > header, > div, > footer {
                padding: 16px;
            }

            input[type="text"], textarea {
                border: 1px solid #ccc;
                width: 100%;
                max-width: 100%;

                &:focus {
                    border-color: $colorOrange;
                }
            }

            textarea {
                min-height: 192px;
            }

            > header {
                display: flex;
                background: #eee;
                border-radius: $borderRadius $borderRadius 0 0;

                img {
                    flex: 0 0 96px;
                }

                .meta {
                    flex: 1;
                    padding-left: 8px;

                    h1 {
                        font-size: 24px;
                        line-height: 28px;
                        margin-bottom: 4px;
                    }

                    .mixed {
                        opacity: .5;
                    }
                }
            }
        }
    }
</style>
