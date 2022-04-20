<template>
  <div class="edit-song" data-testid="edit-song-form" @keydown.esc="maybeClose" tabindex="0">
    <SoundBar v-if="loading"/>
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
        <Btn type="submit">Update</Btn>
        <Btn @click.prevent="maybeClose" class="btn-cancel" white>Cancel</Btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, reactive, ref, toRefs } from 'vue'
import { isEqual, union } from 'lodash'

import { alerts, br2nl, getDefaultCover, arrayify } from '@/utils'
import { songInfo } from '@/services/info'
import { albumStore, artistStore, songStore } from '@/stores'

import { TypeAheadConfig } from 'koel/types/ui'

interface EditFormData {
  title: string
  albumName: string
  artistName: string
  lyrics: string
  track: number | null
  compilationState: number
}

type TabName = 'details' | 'lyrics'

const COMPILATION_STATES = {
  NONE: 0, // No songs belong to a compilation album
  ALL: 1, // All songs belong to compilation album(s)
  SOME: 2 // Some songs belong to compilation album(s)
}

const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))
const Typeahead = defineAsyncComponent(() => import('@/components/ui/typeahead.vue'))

const props = withDefaults(defineProps<{ songs: Song[], initialTab: TabName }>(), { songs: [], initialTab: 'details' })
const { songs, initialTab } = toRefs(props)

const compilationStateChk = ref(null as unknown as HTMLInputElement)
const mutatedSongs = ref<Song[]>([])
const currentView = ref(null as unknown as TabName)
const loading = ref(true)
const artistState = reactive(artistStore.state)
const albumState = reactive(albumStore.state)

const artistTypeAheadConfig: TypeAheadConfig = {
  displayKey: 'name',
  filterKey: 'name',
  name: 'artist'
}

const albumTypeAheadConfig: TypeAheadConfig = {
  displayKey: 'name',
  filterKey: 'name',
  name: 'album'
}

/**
 * In order not to mess up the original songs, we manually assign and manipulate their attributes.
 */
const formData = reactive<EditFormData>({
  title: '',
  albumName: '',
  artistName: '',
  lyrics: '',
  track: null,
  compilationState: COMPILATION_STATES.NONE
})

const initialFormData = ref(null as unknown as EditFormData)

const editingOnlyOneSong = computed(() => mutatedSongs.value.length === 1)
const allSongsAreFromSameArtist = computed(() => new Set(mutatedSongs.value.map(song => song.artist.id)).size === 1)
const allSongsAreInSameAlbum = computed(() => new Set(mutatedSongs.value.map(song => song.album.id)).size === 1)
const coverUrl = computed(() => allSongsAreInSameAlbum.value ? mutatedSongs.value[0].album.cover : getDefaultCover())

const compilationState = computed(() => {
  const albums = mutatedSongs.value.reduce((acc: Album[], song): Album[] => union(acc, [song.album]), [])
  const compiledAlbums = albums.filter(album => album.is_compilation)

  if (!compiledAlbums.length) {
    formData.compilationState = COMPILATION_STATES.NONE
  } else if (compiledAlbums.length === albums.length) {
    formData.compilationState = COMPILATION_STATES.ALL
  } else {
    formData.compilationState = COMPILATION_STATES.SOME
  }

  return formData.compilationState
})

const displayedTitle = computed(() => {
  return editingOnlyOneSong.value ? formData.title : `${mutatedSongs.value.length} songs selected`
})

const displayedArtistName = computed(() => {
  return allSongsAreFromSameArtist.value || formData.artistName ? formData.artistName : 'Mixed Artists'
})

const displayedAlbumName = computed(() => {
  return allSongsAreInSameAlbum.value || formData.albumName ? formData.albumName : 'Mixed Albums'
})

const isPristine = computed(() => isEqual(formData, initialFormData.value))

const initCompilationStateCheckbox = async () => {
  // Wait for the next DOM update, because the form is dynamically
  // attached into DOM in conjunction with `this.loading` data binding.
  await nextTick()
  const checkbox = compilationStateChk.value

  switch (compilationState.value) {
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
}

const open = async () => {
  mutatedSongs.value = arrayify(songs.value)
  currentView.value = initialTab!.value
  const firstSong = mutatedSongs.value[0]

  if (editingOnlyOneSong.value) {
    formData.title = firstSong.title
    formData.albumName = firstSong.album.name
    formData.artistName = firstSong.artist.name

    // If we're editing only one song and the song's info (including lyrics)
    // hasn't been loaded, load it now.
    if (!firstSong.infoRetrieved) {
      loading.value = true

      try {
        await songInfo.fetch(firstSong)
        formData.lyrics = br2nl(firstSong.lyrics)
        formData.track = firstSong.track || null
      } catch (e) {
        console.error(e)
      } finally {
        loading.value = false
        await initCompilationStateCheckbox()
      }
    } else {
      loading.value = false
      formData.lyrics = br2nl(firstSong.lyrics)
      formData.track = firstSong.track || null
      await initCompilationStateCheckbox()
    }
  } else {
    formData.albumName = allSongsAreInSameAlbum.value ? firstSong.album.name : ''
    formData.artistName = allSongsAreFromSameArtist.value ? firstSong.artist.name : ''
    loading.value = false
    await initCompilationStateCheckbox()
  }
}

/**
 * Manually set the compilation state.
 * We can't use v-model here due to the tri-state nature of the property.
 * Also, following iTunes style, we don't support circular switching of the states -
 * once the user clicks the checkbox, there's no going back to indeterminate state.
 */
const changeCompilationState = () => {
  const checkbox = compilationStateChk.value
  formData.compilationState = checkbox.checked ? COMPILATION_STATES.ALL : COMPILATION_STATES.NONE
}

const emit = defineEmits(['close'])

const close = () => emit('close')

const maybeClose = () => {
  if (isPristine.value) {
    close()
    return
  }

  alerts.confirm('Discard all changes?', close)
}

const submit = async () => {
  loading.value = true

  try {
    await songStore.update(mutatedSongs.value, formData)
    close()
  } finally {
    loading.value = false
  }
}

open()
initialFormData.value = Object.assign({}, formData)
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
