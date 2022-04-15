<template>
  <div class="edit-song" data-testid="edit-song-form" @keydown.esc="maybeClose" tabindex="0">
    <sound-bar v-if="loading"/>
    <form v-else @submit.prevent="submit">
      <header>
        <img :src="coverUrl" width="96" height="96" alt="Album's cover">
        <hgroup class="meta">
          <h1 :class="{ mixed: !editingOnlyOneSong }">{{ displayedTitle }}</h1>
          <h2 :class="{ mixed: !allSongsAreFromSameArtist && !formData.artistName }">{{ displayedArtistName }}</h2>
          <h2 :class="{ mixed: !allSongsAreInSameAlbum && !formData.albumName }">{{ displayedAlbumName }}</h2>
        </hgroup>
      </header>

      <div class="tabs">
        <div class="clear" role="tablist">
          <button
            :aria-selected="currentView === 'details'"
            @click.prevent="currentView = 'details'"
            aria-controls="editSongPanelDetails"
            id="editSongTabDetails"
            role="tab"
          >
            Details
          </button>
          <button
            @click.prevent="currentView = 'lyrics'"
            v-if="editingOnlyOneSong"
            :aria-selected="currentView === 'lyrics'"
            aria-controls="editSongPanelLyrics"
            id="editSongTabLyrics"
            role="tab"
            data-testid="edit-song-lyrics-tab"
          >
            Lyrics
          </button>
        </div>

        <div class="panes">
          <div
            aria-labelledby="editSongTabDetails"
            id="editSongPanelDetails"
            role="tabpanel"
            tabindex="0"
            v-show="currentView === 'details'"
          >
            <div class="form-row" v-if="editingOnlyOneSong">
              <label>Title</label>
              <input title="Title" name="title" type="text" v-model="formData.title" v-koel-focus>
            </div>

            <div class="form-row">
              <label>Artist</label>
              <typeahead
                :items="artistState.artists"
                :config="artistTypeAheadConfig"
                v-model="formData.artistName"
              />
            </div>

            <div class="form-row">
              <label>Album</label>
              <typeahead
                :items="albumState.albums"
                :config="albumTypeAheadConfig"
                v-model="formData.albumName"
              />
            </div>

            <div class="form-row">
              <label class="small">
                <input
                  type="checkbox"
                  name="is_compilation"
                  @change="changeCompilationState"
                  ref="compilationStateChk"
                />
                Album is a compilation of songs by various artists
              </label>
            </div>

            <div class="form-row" v-if="editingOnlyOneSong">
              <label>Track</label>
              <input name="track" type="text" pattern="\d*" v-model="formData.track"
                     title="Empty or a number">
            </div>
          </div>

          <div
            aria-labelledby="editSongTabLyrics"
            id="editSongPanelLyrics"
            role="tabpanel"
            tabindex="0"
            v-if="editingOnlyOneSong"
            v-show="currentView === 'lyrics'"
          >
            <div class="form-row">
              <textarea title="Lyrics" name="lyrics" v-model="formData.lyrics" v-koel-focus></textarea>
            </div>
          </div>
        </div>
      </div>

      <footer>
        <btn type="submit">Update</btn>
        <btn @click.prevent="maybeClose" class="btn-cancel" white>Cancel</btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts">
import { union, isEqual } from 'lodash'

import { br2nl, getDefaultCover, alerts } from '@/utils'
import { songInfo } from '@/services/info'
import { artistStore, albumStore, songStore } from '@/stores'

import Vue, { PropOptions } from 'vue'
import { TypeAheadConfig } from 'koel/types/ui'

interface EditFormData {
  title: string
  albumName: string
  artistName: string
  lyrics: string
  track: number | null
  compilationState: number
}

const COMPILATION_STATES = {
  NONE: 0, // No songs belong to a compilation album
  ALL: 1, // All songs belong to compilation album(s)
  SOME: 2 // Some of the songs belong to compilation album(s)
}

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue'),
    SoundBar: () => import('@/components/ui/sound-bar.vue'),
    Typeahead: () => import('@/components/ui/typeahead.vue')
  },

  props: {
    songs: {
      required: true,
      type: [Array, Object]
    } as PropOptions<Song | Song[]>,

    initialTab: {
      type: String,
      default: 'details',
      validator: (value: string): boolean => ['details', 'lyrics'].includes(value)
    }
  },

  data: () => ({
    mutatedSongs: [] as Song[],
    currentView: '',
    loading: true,

    artistState: artistStore.state,
    artistTypeAheadConfig: {
      displayKey: 'name',
      filterKey: 'name',
      name: 'artist'
    } as TypeAheadConfig,

    albumState: albumStore.state,
    albumTypeAheadConfig: {
      displayKey: 'name',
      filterKey: 'name',
      name: 'album'
    } as TypeAheadConfig,

    /**
     * In order not to mess up the original songs, we manually assign and manipulate
     * their attributes.
     */
    formData: {
      title: '',
      albumName: '',
      artistName: '',
      lyrics: '',
      track: null,
      compilationState: 0
    } as EditFormData,

    initialFormData: null as unknown as EditFormData
  }),

  computed: {
    editingOnlyOneSong (): boolean {
      return this.mutatedSongs.length === 1
    },

    allSongsAreFromSameArtist (): boolean {
      return this.mutatedSongs.every((song: Song): boolean => song.artist.id === this.mutatedSongs[0].artist.id)
    },

    allSongsAreInSameAlbum (): boolean {
      return this.mutatedSongs.every((song: Song): boolean => song.album.id === this.mutatedSongs[0].album.id)
    },

    coverUrl (): string {
      return this.allSongsAreInSameAlbum ? this.mutatedSongs[0].album.cover : getDefaultCover()
    },

    compilationState (): number {
      const albums = this.mutatedSongs.reduce((acc: Album[], song: Song): Album[] => union(acc, [song.album]), [])
      const compiledAlbums = albums.filter((album: Album): boolean => album.is_compilation)

      if (!compiledAlbums.length) {
        this.formData.compilationState = COMPILATION_STATES.NONE
      } else if (compiledAlbums.length === albums.length) {
        this.formData.compilationState = COMPILATION_STATES.ALL
      } else {
        this.formData.compilationState = COMPILATION_STATES.SOME
      }

      return this.formData.compilationState
    },

    displayedTitle (): string {
      return this.editingOnlyOneSong ? this.formData.title : `${this.mutatedSongs.length} songs selected`
    },

    displayedArtistName (): string {
      return this.allSongsAreFromSameArtist || this.formData.artistName
        ? this.formData.artistName
        : 'Mixed Artists'
    },

    displayedAlbumName (): string {
      return this.allSongsAreInSameAlbum || this.formData.albumName
        ? this.formData.albumName
        : 'Mixed Albums'
    },

    isPristine (): boolean {
      return isEqual(this.formData, this.initialFormData)
    }
  },

  methods: {
    async open (): Promise<void> {
      this.mutatedSongs = ([] as Song[]).concat(this.songs)
      this.currentView = this.initialTab

      if (this.editingOnlyOneSong) {
        this.formData.title = this.mutatedSongs[0].title
        this.formData.albumName = this.mutatedSongs[0].album.name
        this.formData.artistName = this.mutatedSongs[0].artist.name

        // If we're editing only one song and the song's info (including lyrics)
        // hasn't been loaded, load it now.
        if (!this.mutatedSongs[0].infoRetrieved) {
          this.loading = true

          try {
            await songInfo.fetch(this.mutatedSongs[0])
            this.formData.lyrics = br2nl(this.mutatedSongs[0].lyrics)
            this.formData.track = this.mutatedSongs[0].track || null
          } catch (e) {
            console.error(e)
          } finally {
            this.loading = false
            this.initCompilationStateCheckbox()
          }
        } else {
          this.loading = false
          this.formData.lyrics = br2nl(this.mutatedSongs[0].lyrics)
          this.formData.track = this.mutatedSongs[0].track || null
          this.initCompilationStateCheckbox()
        }
      } else {
        this.formData.albumName = this.allSongsAreInSameAlbum ? this.mutatedSongs[0].album.name : ''
        this.formData.artistName = this.allSongsAreFromSameArtist ? this.mutatedSongs[0].artist.name : ''
        this.loading = false
        this.initCompilationStateCheckbox()
      }
    },

    initCompilationStateCheckbox (): void {
      // This must be wrapped in a $nextTick callback, because the form is dynamically
      // attached into DOM in conjunction with `this.loading` data binding.
      this.$nextTick((): void => {
        const checkbox = this.$refs.compilationStateChk as HTMLInputElement

        switch (this.compilationState) {
          case COMPILATION_STATES.ALL:
            checkbox.checked = true
            checkbox.indeterminate = false
            break

          case COMPILATION_STATES.NONE:
            checkbox.checked = false
            checkbox.indeterminate = false
            break

          default:
            checkbox.checked = false
            checkbox.indeterminate = true
            break
        }
      })
    },

    /**
     * Manually set the compilation state.
     * We can't use v-model here due to the tri-state nature of the property.
     * Also, following iTunes style, we don't support circular switching of the states -
     * once the user clicks the checkbox, there's no going back to indeterminate state.
     */
    changeCompilationState (): void {
      const checkbox = this.$refs.compilationStateChk as HTMLInputElement
      this.formData.compilationState = checkbox.checked ? COMPILATION_STATES.ALL : COMPILATION_STATES.NONE
    },

    close (): void {
      this.$emit('close')
    },

    maybeClose (): void {
      if (this.isPristine) {
        this.close()
        return
      }

      alerts.confirm('Discard all changes?', () => this.close())
    },

    async submit (): Promise<void> {
      this.loading = true

      try {
        await songStore.update(this.mutatedSongs, this.formData)
        this.close()
      } finally {
        this.loading = false
      }
    }
  },

  async created (): Promise<void> {
    await this.open()
    this.initialFormData = Object.assign({}, this.formData)
  }
})
</script>

<style lang="scss" scoped>
form {
  .tabs {
    padding: 0;
  }

  > header {
    img {
      flex: 0 0 96px;
    }

    .meta {
      flex: 1;
      padding-left: 1rem;

      .mixed {
        opacity: .5;
      }
    }
  }
}
</style>
