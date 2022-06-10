<template>
  <div class="edit-song" data-testid="edit-song-form" tabindex="0" @keydown.esc="maybeClose">
    <SoundBar v-if="loading"/>
    <form v-else @submit.prevent="submit">
      <header>
        <img :src="coverUrl" alt="Album's cover" height="96" width="96">
        <div class="meta">
          <h1 :class="{ mixed: !editingOnlyOneSong }">{{ displayedTitle }}</h1>
          <h2 :class="{ mixed: !allSongsAreFromSameArtist && !formData.artist_name }">{{ displayedArtistName }}</h2>
          <h2 :class="{ mixed: !allSongsAreInSameAlbum && !formData.album_name }">{{ displayedAlbumName }}</h2>
        </div>
      </header>

      <div class="tabs">
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
              <label>Title</label>
              <input v-model="formData.title" v-koel-focus name="title" title="Title" type="text">
            </div>

            <div class="form-row">
              <label>Artist</label>
              <input
                v-model="formData.artist_name"
                :placeholder="inputPlaceholder"
                name="artist"
                type="text"
              >
            </div>

            <div class="form-row">
              <label>Album</label>
              <input
                v-model="formData.album_name"
                :placeholder="inputPlaceholder"
                name="album"
                type="text"
              >
            </div>

            <div class="form-row">
              <label>Album Artist</label>
              <input
                v-model="formData.album_artist_name"
                name="album_artist"
                :placeholder="inputPlaceholder"
                type="text"
              >
            </div>

            <div class="form-row">
              <div class="cols">
                <div>
                  <label>Track</label>
                  <input
                    v-model="formData.track"
                    name="track"
                    pattern="\d*"
                    title="Empty or a number"
                    type="text"
                    :placeholder="inputPlaceholder"
                  >
                </div>
                <div>
                  <label>Disc</label>
                  <input
                    v-model="formData.disc"
                    name="disc"
                    pattern="\d*"
                    title="Empty or a number"
                    type="text"
                    :placeholder="inputPlaceholder"
                  >
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
import { computed, defineAsyncComponent, reactive, ref, toRefs } from 'vue'
import { isEqual } from 'lodash'

import { alerts, arrayify, defaultCover, pluralize } from '@/utils'
import { songStore } from '@/stores'

type EditFormData = Pick<Song, 'title' | 'album_name' | 'artist_name' | 'album_artist_name' | 'lyrics' | 'track' | 'disc'>

type TabName = 'details' | 'lyrics'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/SoundBar.vue'))

const props = withDefaults(defineProps<{ songs: Song[], initialTab: TabName }>(), { initialTab: 'details' })
const { songs, initialTab } = toRefs(props)

const mutatedSongs = ref<Song[]>([])
const currentView = ref<TabName>()
const loading = ref(false)

/**
 * In order not to mess up the original songs, we manually assign and manipulate their attributes.
 */
const formData = reactive<EditFormData>({
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

const coverUrl = computed(() => allSongsAreInSameAlbum.value ? mutatedSongs.value[0].album_cover : defaultCover)

const allSongsShareSameValue = (key: keyof EditFormData) => {
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
  mutatedSongs.value = arrayify(songs.value)
  currentView.value = initialTab.value
  const firstSong = mutatedSongs.value[0]

  formData.title = allSongsShareSameValue('title') ? firstSong.title : ''
  formData.album_name = allSongsShareSameValue('album_name') ? firstSong.album_name : ''
  formData.artist_name = allSongsShareSameValue('artist_name') ? firstSong.artist_name : ''
  formData.album_artist_name = allSongsShareSameValue('album_artist_name') ? firstSong.album_artist_name : ''
  formData.lyrics = editingOnlyOneSong.value ? firstSong.lyrics : ''
  formData.track = allSongsShareSameValue('track') ? firstSong.track : null
  formData.disc = allSongsShareSameValue('disc') ? firstSong.disc : null

  Object.assign(initialFormData, formData)
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
    alerts.success(`Updated ${pluralize(mutatedSongs.value.length, 'song')}.`)
    close()
  } finally {
    loading.value = false
  }
}

open()
</script>

<style lang="scss" scoped>
form {
  max-width: 540px;

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
