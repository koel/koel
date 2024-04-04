<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <span class="cover" :style="{ backgroundImage: `url(${coverUrl})` }" />
      <div class="meta">
        <h1 :class="{ mixed: !editingOnlyOneSong }">{{ displayedTitle }}</h1>
        <h2
          data-testid="displayed-artist-name"
          :class="{ mixed: !allSongsAreFromSameArtist && !formData.artist_name }"
        >
          {{ displayedArtistName }}
        </h2>
        <h2
          data-testid="displayed-album-name"
          :class="{ mixed: !allSongsAreInSameAlbum && !formData.album_name }"
        >
          {{ displayedAlbumName }}
        </h2>
      </div>
    </header>

    <main class="tabs">
      <div class="clear" role="tablist">
        <button
          id="editSongTabDetails"
          :aria-selected="currentTab === 'details'"
          aria-controls="editSongPanelDetails"
          role="tab"
          type="button"
          @click.prevent="currentTab = 'details'"
        >
          Details
        </button>
        <button
          v-if="editingOnlyOneSong"
          id="editSongTabLyrics"
          :aria-selected="currentTab === 'lyrics'"
          aria-controls="editSongPanelLyrics"
          data-testid="edit-song-lyrics-tab"
          role="tab"
          type="button"
          @click.prevent="currentTab = 'lyrics'"
        >
          Lyrics
        </button>
      </div>

      <div class="panes">
        <div
          v-show="currentTab === 'details'"
          id="editSongPanelDetails"
          aria-labelledby="editSongTabDetails"
          role="tabpanel"
          tabindex="0"
        >
          <div v-if="editingOnlyOneSong" class="form-row">
            <label>
              Title
              <input
                v-model="formData.title"
                v-koel-focus
                data-testid="title-input"
                name="title"
                title="Title"
                type="text"
              >
            </label>
          </div>

          <div class="form-row cols">
            <label>
              Artist
              <input
                v-model="formData.artist_name"
                :placeholder="inputPlaceholder"
                data-testid="artist-input"
                name="artist"
                type="text"
              >
            </label>
            <label>
              Album Artist
              <input
                v-model="formData.album_artist_name"
                :placeholder="inputPlaceholder"
                data-testid="albumArtist-input"
                name="album_artist"
                type="text"
              >
            </label>
          </div>

          <div class="form-row">
            <label>
              Album
              <input
                v-model="formData.album_name"
                :placeholder="inputPlaceholder"
                data-testid="album-input"
                name="album"
                type="text"
              >
            </label>
          </div>

          <div class="form-row cols">
            <label>
              Track
              <input
                v-model="formData.track"
                :placeholder="inputPlaceholder"
                data-testid="track-input"
                min="1"
                name="track"
                type="number"
              >
            </label>
            <label>
              Disc
              <input
                v-model="formData.disc"
                :placeholder="inputPlaceholder"
                data-testid="disc-input"
                min="1"
                name="disc"
                type="number"
              >
            </label>
          </div>

          <div class="form-row cols">
            <label>
              Genre
              <input
                v-model="formData.genre"
                :placeholder="inputPlaceholder"
                data-testid="genre-input"
                name="genre"
                type="text"
                list="genres"
              >
              <datalist id="genres">
                <option v-for="genre in genres" :key="genre" :value="genre" />
              </datalist>
            </label>
            <label>
              Year
              <input
                v-model="formData.year"
                :placeholder="inputPlaceholder"
                data-testid="year-input"
                name="year"
                type="number"
              >
            </label>
          </div>
        </div>

        <div
          v-if="editingOnlyOneSong"
          v-show="currentTab === 'lyrics'"
          id="editSongPanelLyrics"
          aria-labelledby="editSongTabLyrics"
          role="tabpanel"
          tabindex="0"
        >
          <div class="form-row">
            <textarea
              v-model="formData.lyrics"
              v-koel-focus
              data-testid="lyrics-input"
              name="lyrics"
              title="Lyrics"
            />
          </div>
        </div>
      </div>
    </main>

    <footer>
      <Btn type="submit">Update</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { isEqual } from 'lodash'
import { defaultCover, eventBus, logger, pluralize } from '@/utils'
import { songStore, SongUpdateData } from '@/stores'
import { useDialogBox, useMessageToaster, useModal, useOverlay } from '@/composables'
import { genres } from '@/config'

import Btn from '@/components/ui/Btn.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const { getFromContext } = useModal()

const songs = getFromContext<Song[]>('songs')
const currentTab = ref(getFromContext<EditSongFormTabName>('initialTab'))

const editingOnlyOneSong = songs.length === 1
const inputPlaceholder = editingOnlyOneSong ? '' : 'Leave unchanged'

const allSongsShareSameValue = (key: keyof Song) => {
  if (editingOnlyOneSong) return true
  return new Set(songs.map(song => song[key])).size === 1
}

const allSongsAreFromSameArtist = allSongsShareSameValue('artist_name')
const allSongsAreInSameAlbum = allSongsShareSameValue('album_id')
const coverUrl = allSongsAreInSameAlbum ? (songs[0].album_cover || defaultCover) : defaultCover

const formData = reactive<SongUpdateData>({
  title: allSongsShareSameValue('title') ? songs[0].title : '',
  album_name: allSongsAreInSameAlbum ? songs[0].album_name : '',
  artist_name: allSongsAreFromSameArtist ? songs[0].artist_name : '',
  album_artist_name: '',
  lyrics: editingOnlyOneSong ? songs[0].lyrics : '',
  track: allSongsShareSameValue('track') && songs[0].track !== 0 ? songs[0].track : null,
  disc: allSongsShareSameValue('disc') && songs[0].disc !== 0 ? songs[0].disc : null,
  year: allSongsShareSameValue('year') ? songs[0].year : null,
  genre: allSongsShareSameValue('genre') ? songs[0].genre : ''
})

// If the album artist(s) is the same as the artist(s), we set the form value as empty to not confuse the user
// and make it less error-prone.
if (allSongsAreInSameAlbum && allSongsAreFromSameArtist && songs[0].album_artist_id === songs[0].artist_id) {
  formData.album_artist_name = ''
} else {
  formData.album_artist_name = allSongsShareSameValue('album_artist_name') ? songs[0].album_artist_name : ''
}

if (!editingOnlyOneSong) {
  delete formData.title
  delete formData.lyrics
}

const initialFormData = Object.assign({}, formData)

const displayedTitle = computed(() => {
  return editingOnlyOneSong ? formData.title : `${songs.length} songs selected`
})

const displayedArtistName = computed(() => {
  return allSongsAreFromSameArtist || formData.artist_name ? formData.artist_name : 'Mixed Artists'
})

const displayedAlbumName = computed(() => {
  return allSongsAreInSameAlbum || formData.album_name ? formData.album_name : 'Mixed Albums'
})

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (isEqual(formData, initialFormData)) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}

const submit = async () => {
  showOverlay()

  try {
    await songStore.update(songs, formData)
    toastSuccess(`Updated ${pluralize(songs, 'song')}.`)
    eventBus.emit('SONGS_UPDATED')
    close()
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}
</script>

<style lang="postcss" scoped>
form {
  max-width: 540px;

  > main {
    margin-top: 1.125rem;
    padding: 0 !important;
  }

  > header {
    gap: 1.2rem;

    .cover {
      flex: 0 0 84px;
      height: 84px;
      background-size: cover;
      border-radius: 5px;
    }

    .meta {
      flex: 1;

      .mixed {
        opacity: .5;
      }
    }
  }
}
</style>
