<template>
  <div class="edit-song" data-testid="edit-song-form" tabindex="0" @keydown.esc="maybeClose">
    <SoundBar v-if="loading"/>
    <form v-else @submit.prevent="submit">
      <header>
        <img :src="coverUrl" alt="Album's cover" height="96" width="96">
        <div class="meta">
          <h1 :class="{ mixed: !editingOnlyOneSong }">{{ displayedTitle }}</h1>
          <h2 :class="{ mixed: !allSongsAreFromSameArtist && !formData.artistName }">{{ displayedArtistName }}</h2>
          <h2 :class="{ mixed: !allSongsAreInSameAlbum && !formData.albumName }">{{ displayedAlbumName }}</h2>
        </div>
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
            v-show="currentView === 'details'"
            id="editSongPanelDetails"
            aria-labelledby="editSongTabDetails"
            role="tabpanel"
            tabindex="0"
          >
            <div class="form-row" v-if="editingOnlyOneSong">
              <label>Title</label>
              <input v-model="formData.title" v-koel-focus name="title" title="Title" type="text">
            </div>

            <div class="form-row">
              <label>Artist</label>
              <input v-model="formData.artistName" :placeholder="artistNamePlaceholder" list="artistNames" type="text">
              <datalist id="artistNames">
                <option v-for="name in artistNames" :key="name" :value="name"></option>
              </datalist>
            </div>

            <div class="form-row">
              <label>Album</label>
              <input v-model="formData.albumName" :placeholder="albumNamePlaceholder" list="albumNames" type="text">
              <datalist id="albumNames">
                <option v-for="name in albumNames" :key="name" :value="name"></option>
              </datalist>
            </div>

            <div class="form-row">
              <label class="small">
                <input
                  ref="compilationStateCheckbox"
                  name="is_compilation"
                  type="checkbox"
                  @change="changeCompilationState"
                />
                Album is a compilation of songs by various artists
              </label>
            </div>

            <div v-if="editingOnlyOneSong" class="form-row">
              <label>Track</label>
              <input v-model="formData.track" name="track" pattern="\d*" title="Empty or a number" type="text">
            </div>
          </div>

          <div
            v-if="editingOnlyOneSong"
            v-show="currentView === 'lyrics'"
            id="editSongPanelLyrics"
            aria-labelledby="editSongTabLyrics"
            role="tabpanel"
            tabindex="0"
          >
            <div class="form-row">
              <textarea v-model="formData.lyrics" v-koel-focus name="lyrics" title="Lyrics"></textarea>
            </div>
          </div>
        </div>
      </div>

      <footer>
        <Btn type="submit">Update</Btn>
        <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, nextTick, reactive, ref, toRef, toRefs } from 'vue'
import { isEqual, union } from 'lodash'

import { alerts, arrayify, br2nl, getDefaultCover } from '@/utils'
import { songInfo } from '@/services/info'
import { albumStore, artistStore, songStore } from '@/stores'

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
  MIXED: 2 // Some songs belong to compilation album(s)
}

const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))

const props = withDefaults(defineProps<{ songs: Song[], initialTab: TabName }>(), { initialTab: 'details' })
const { songs, initialTab } = toRefs(props)

const compilationStateCheckbox = ref<HTMLInputElement>()
const mutatedSongs = ref<Song[]>([])
const currentView = ref<TabName>()
const loading = ref(true)

const artists = toRef(artistStore.state, 'artists')
const artistNames = computed(() => new Set(artists.value.map(artist => artist.name)))

const albums = toRef(albumStore.state, 'albums')
const albumNames = computed(() => new Set(albums.value.map(album => album.name)))

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

let initialFormData = {}

const editingOnlyOneSong = computed(() => mutatedSongs.value.length === 1)
const allSongsAreFromSameArtist = computed(() => new Set(mutatedSongs.value.map(song => song.artist.id)).size === 1)
const allSongsAreInSameAlbum = computed(() => new Set(mutatedSongs.value.map(song => song.album.id)).size === 1)
const coverUrl = computed(() => allSongsAreInSameAlbum.value ? mutatedSongs.value[0].album.cover : getDefaultCover())
const artistNamePlaceholder = computed(() => editingOnlyOneSong.value ? 'Unknown Artist' : 'Leave unchanged')
const albumNamePlaceholder = computed(() => editingOnlyOneSong.value ? 'Unknown Album' : 'Leave unchanged')

const compilationState = computed(() => {
  const albums = mutatedSongs.value.reduce((acc: Album[], song): Album[] => union(acc, [song.album]), [])
  const compiledAlbums = albums.filter(album => album.is_compilation)

  if (!compiledAlbums.length) {
    formData.compilationState = COMPILATION_STATES.NONE
  } else if (compiledAlbums.length === albums.length) {
    formData.compilationState = COMPILATION_STATES.ALL
  } else {
    formData.compilationState = COMPILATION_STATES.MIXED
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

const isPristine = computed(() => isEqual(formData, initialFormData))

const initCompilationStateCheckbox = async () => {
  // Wait for the next DOM update, because the form is dynamically
  // attached into DOM in conjunction with `this.loading` data binding.
  await nextTick()

  const checkbox = compilationStateCheckbox.value!
  checkbox.checked = compilationState.value === COMPILATION_STATES.ALL
  checkbox.indeterminate = compilationState.value === COMPILATION_STATES.MIXED
}

const open = async () => {
  mutatedSongs.value = arrayify(songs.value)
  currentView.value = initialTab.value
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

  initialFormData = Object.assign(formData)
}

/**
 * Manually set the compilation state.
 * We can't use v-model here due to the tri-state nature of the property.
 * Also, following iTunes style, we don't support circular switching of the states -
 * once the user clicks the checkbox, there's no going back to indeterminate state.
 */
const changeCompilationState = () => {
  formData.compilationState = compilationStateCheckbox.value!.checked ? COMPILATION_STATES.ALL : COMPILATION_STATES.NONE
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
