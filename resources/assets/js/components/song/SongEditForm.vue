<template>
  <div class="edit-song" data-testid="edit-song-form" tabindex="0" @keydown.esc="maybeClose">
    <SoundBars v-if="loading"/>
    <form v-else @submit.prevent="submit">
      <header>
        <span class="cover" :style="{ backgroundImage: `url(${coverUrl})` }"/>
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
            :aria-selected="currentView === 'details'"
            aria-controls="editSongPanelDetails"
            role="tab"
            type="button"
            @click.prevent="currentView = 'details'"
          >
            Details
          </button>
          <button
            v-if="editingOnlyOneSong"
            id="editSongTabLyrics"
            :aria-selected="currentView === 'lyrics'"
            aria-controls="editSongPanelLyrics"
            data-testid="edit-song-lyrics-tab"
            role="tab"
            type="button"
            @click.prevent="currentView = 'lyrics'"
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

            <div class="form-row">
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

            <div class="form-row">
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
              <div class="cols">
                <div>
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
                </div>
                <div>
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
              </div>
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
  </div>
</template>

<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { isEqual } from 'lodash'
import { defaultCover, pluralize, requireInjection } from '@/utils'
import { songStore, SongUpdateData } from '@/stores'
import { DialogBoxKey, EditSongFormInitialTabKey, MessageToasterKey, SongsKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'
import SoundBars from '@/components/ui/SoundBars.vue'

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)
const [initialTab] = requireInjection(EditSongFormInitialTabKey)
const [songs] = requireInjection(SongsKey)

const currentView = ref<EditSongFormTabName>('details')
const loading = ref(false)

const mutatedSongs = computed(() => songs.value)

/**
 * In order not to mess up the original songs, we manually assign and manipulate their attributes.
 */
const formData = reactive<SongUpdateData>({
  title: '',
  album_name: '',
  artist_name: '',
  album_artist_name: '',
  lyrics: '',
  track: null,
  disc: null
})

const initialFormData = {}

const editingOnlyOneSong = computed(() => mutatedSongs.value.length === 1)
const inputPlaceholder = computed(() => editingOnlyOneSong.value ? '' : 'Leave unchanged')

const allSongsAreFromSameArtist = computed(() => allSongsShareSameValue('artist_name'))
const allSongsAreInSameAlbum = computed(() => allSongsShareSameValue('album_name'))

const coverUrl = computed(() => allSongsAreInSameAlbum.value
  ? mutatedSongs.value[0].album_cover || defaultCover
  : defaultCover
)

const allSongsShareSameValue = (key: keyof SongUpdateData) => {
  if (editingOnlyOneSong.value) return true
  return new Set(mutatedSongs.value.map(song => song[key])).size === 1
}

const displayedTitle = computed(() => {
  return editingOnlyOneSong.value ? formData.title : `${mutatedSongs.value.length} songs selected`
})

const displayedArtistName = computed(() => {
  return allSongsAreFromSameArtist.value || formData.artist_name ? formData.artist_name : 'Mixed Artists'
})

const displayedAlbumName = computed(() => {
  return allSongsAreInSameAlbum.value || formData.album_name ? formData.album_name : 'Mixed Albums'
})

const isPristine = computed(() => isEqual(formData, initialFormData))

const open = async () => {
  currentView.value = initialTab.value
  const firstSong = mutatedSongs.value[0]

  formData.title = allSongsShareSameValue('title') ? firstSong.title : ''
  formData.album_name = allSongsShareSameValue('album_name') ? firstSong.album_name : ''
  formData.artist_name = allSongsShareSameValue('artist_name') ? firstSong.artist_name : ''

  // If the album artist(s) is the same as the artist(s), we set the form value as empty to not confuse the user
  // and make it less error-prone.
  if (
    allSongsShareSameValue('artist_name') && allSongsShareSameValue('album_artist_name')
    && firstSong.album_artist_id === firstSong.artist_id
  ) {
    formData.album_artist_name = ''
  } else {
    formData.album_artist_name = allSongsShareSameValue('album_artist_name') ? firstSong.album_artist_name : ''
  }

  formData.lyrics = editingOnlyOneSong.value ? firstSong.lyrics : ''

  formData.track = allSongsShareSameValue('track') ? firstSong.track : null
  formData.track = formData.track || null // if 0, just don't show it

  formData.disc = allSongsShareSameValue('disc') ? firstSong.disc : null
  formData.disc = formData.disc || null // if 0, just don't show it

  if (!editingOnlyOneSong.value) {
    delete formData.title
    delete formData.lyrics
  }

  Object.assign(initialFormData, formData)
}

const emit = defineEmits(['close'])
const close = () => emit('close')

const maybeClose = async () => {
  if (isPristine.value) {
    close()
    return
  }

  await dialog.value.confirm('Discard all changes?') && close()
}

const submit = async () => {
  loading.value = true

  try {
    await songStore.update(mutatedSongs.value, formData)
    toaster.value.success(`Updated ${pluralize(mutatedSongs.value.length, 'song')}.`)
    close()
  } finally {
    loading.value = false
  }
}

onMounted(async () => await open())
</script>

<style lang="scss" scoped>
form {
  max-width: 540px;

  .tabs {
    padding: 0;
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

  .form-row .cols {
    display: flex;
    place-content: space-between;
    gap: 1rem;

    > div {
      flex: 1;
    }
  }
}
</style>
