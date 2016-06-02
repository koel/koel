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
                            <div class="form-row" v-if="editSingle">
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
                            <div class="form-row">
                                <label class="small">
                                    <input type="checkbox" @change="changeCompilationState" v-el:compilation-state-chk />
                                    Album is a compilation of songs by various artists
                                </label>
                                <label class="small warning" v-if="needsReload">
                                    Koel will reload after saving.
                                </label>
                            </div>
                            <div class="form-row" v-show="editSingle">
                                <label>Track</label>
                                <input type="number" min="0" v-model="formData.track">
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
    import { every, filter } from 'lodash';

    import { br2nl } from '../../services/utils';
    import artistStore from '../../stores/artist';
    import albumStore from '../../stores/album';
    import songStore from '../../stores/song';

    import soundBar from '../shared/sound-bar.vue';
    import typeahead from '../shared/typeahead.vue';

    const COMPILATION_STATES = {
        NONE: 0, // No songs belong to a compilation album
        ALL: 1, // All songs belong to compilation album(s)
        SOME: 2, // Some of the songs belong to compilation album(s)
    };

    export default {
        components: { soundBar, typeahead },

        data() {
            return {
                shown: false,
                songs: [],
                currentView: '',
                loading: false,
                needsReload: false,

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
                    track: '',
                    compilationState: null,
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
                return every(this.songs, song => song.artist.id === this.songs[0].artist.id);
            },

            /**
             * Determine if all songs we're editing are from the same album.
             *
             * @return {boolean}
             */
            inSameAlbum() {
                return every(this.songs, song => song.album.id === this.songs[0].album.id);
            },

            /**
             * Determine the compilation state of the songs.
             *
             * @return {Number}
             */
            compilationState() {
                let contributedSongs = filter(this.songs, song => song.contributing_artist_id)

                if (!contributedSongs.length) {
                    this.formData.compilationState = COMPILATION_STATES.NONE
                } else if (contributedSongs.length === this.songs.length) {
                    this.formData.compilationState = COMPILATION_STATES.ALL;
                } else {
                    this.formData.compilationState = COMPILATION_STATES.SOME;
                }

                return this.formData.compilationState;
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
                this.needsReload = false;

                if (this.editSingle) {
                    this.formData.title = this.songs[0].title;
                    this.formData.albumName = this.songs[0].album.name;
                    this.formData.artistName = this.songs[0].artist.name;

                    // If we're editing only one song and the song's info (including lyrics)
                    // hasn't been loaded, load it now.
                    if (!this.songs[0].infoRetrieved) {
                        this.loading = true;

                        songStore.getInfo(this.songs[0], () => {
                            this.loading = false;
                            this.formData.lyrics = br2nl(this.songs[0].lyrics);
                            this.formData.track = this.songs[0].track;
                            this.initCompilationStateCheckbox();
                        });
                    } else {
                        this.formData.lyrics = br2nl(this.songs[0].lyrics);
                        this.formData.track = this.songs[0].track;
                        this.initCompilationStateCheckbox();
                    }
                } else {
                    this.formData.albumName = this.inSameAlbum ? this.songs[0].album.name : '';
                    this.formData.artistName = this.bySameArtist ? this.songs[0].artist.name : '';
                    this.loading = false;
                    this.initCompilationStateCheckbox();
                }
            },

            /**
             * Initialize the compilation state's checkbox of the editing songs' album(s).
             */
            initCompilationStateCheckbox() {
                // This must be wrapped in a $nextTick callback, because the form is dynamically
                // attached into DOM in conjunction with `this.loading` data binding.
                this.$nextTick(() => {
                    let chk = this.$els.compilationStateChk;

                    switch (this.compilationState) {
                        case COMPILATION_STATES.ALL:
                            chk.checked = true;
                            chk.indeterminate = false;
                            break;
                        case COMPILATION_STATES.NONE:
                            chk.checked = false;
                            chk.indeterminate = false;
                            break;
                        default:
                            chk.checked = false;
                            chk.indeterminate = true;
                            break;
                    }
                });
            },

            /**
             * Manually set the compilation state.
             * We can't use v-model here due to the tri-state nature of the property.
             * Also, following iTunes style, we don't support circular switching of the states -
             * once the user clicks the checkbox, there's no going back to indeterminate state.
             */
            changeCompilationState(e) {
                this.formData.compilationState = e.target.checked ? COMPILATION_STATES.ALL : COMPILATION_STATES.NONE;
                this.needsReload = true;
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
                    if (this.needsReload) {
                        this.$root.forceReloadWindow();
                    }
                }, () => {
                    this.loading = false;
                });
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

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

            input[type="checkbox"] {
                border: 1px solid #ccc;
            }

            .form-row:first-child {
                margin-top: 0;
            }

            > header, > div, > footer {
                padding: 16px;
            }

            > div {
                padding-bottom: 0;
            }

            input[type="text"], input[type="number"], textarea {
                border: 1px solid #ccc;
                width: 100%;
                max-width: 100%;

                &:focus {
                    border-color: $colorOrange;
                }
            }

            .warning {
                color: #f00;
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
                        font-size: 1.8rem;
                        line-height: 2.2rem;
                        margin-bottom: .3rem;
                    }

                    .mixed {
                        opacity: .5;
                    }
                }
            }
        }
    }
</style>
