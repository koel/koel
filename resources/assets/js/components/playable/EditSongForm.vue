<template>
  <form class="max-w-[540px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header class="gap-4">
      <img :src="coverUrl" alt="" class="w-[84px] aspect-square object-cover object-center rounded-md">
      <div class="flex-1 flex flex-col justify-center overflow-hidden">
        <h1 :class="{ mixed: editingMultipleSongs }">{{ displayedTitle }}</h1>
        <h2
          :class="{ mixed: !allSongsAreFromSameArtist && !data.artist_name }"
          data-testid="displayed-artist-name"
        >
          {{ displayedArtistName }}
        </h2>
        <h2
          :class="{ mixed: !allSongsAreInSameAlbum && !data.album_name }"
          data-testid="displayed-album-name"
        >
          {{ displayedAlbumName }}
        </h2>
      </div>
    </header>

    <Tabs class="mt-4">
      <TabList v-if="editingOnlyOneSong">
        <TabButton
          id="editSongTabDetails"
          :selected="currentTab === 'details'"
          aria-controls="editSongPanelDetails"
          @click="currentTab = 'details'"
        >
          {{ t('songs.details') }}
        </TabButton>
        <TabButton
          id="editSongTabLyrics"
          :selected="currentTab === 'lyrics'"
          aria-controls="editSongPanelLyrics"
          data-testid="edit-song-lyrics-tab"
          @click="currentTab = 'lyrics'"
        >
          {{ t('songs.lyrics') }}
        </TabButton>
      </TabList>

      <TabPanelContainer>
        <TabPanel
          v-show="currentTab === 'details'"
          id="editSongPanelDetails"
          aria-labelledby="editSongTabDetails"
          class="space-y-5"
        >
          <FormRow v-if="editingOnlyOneSong">
            <template #label>{{ t('songs.title') }}</template>
            <TextInput v-model="data.title" v-koel-focus data-testid="title-input" name="title" :title="t('songs.title')" />
          </FormRow>

          <FormRow :cols="2">
            <FormRow>
              <template #label>{{ t('songs.artist') }}</template>
              <TextInput
                v-model="data.artist_name"
                :placeholder="inputPlaceholder"
                data-testid="artist-input"
                name="artist"
              />
            </FormRow>

            <FormRow>
              <template #label>{{ t('songs.albumArtist') }}</template>
              <TextInput
                v-model="data.album_artist_name"
                :placeholder="inputPlaceholder"
                data-testid="albumArtist-input"
                name="album_artist"
              />
            </FormRow>
          </FormRow>

          <FormRow>
            <template #label>{{ t('songs.album') }}</template>
            <TextInput
              v-model="data.album_name"
              :placeholder="inputPlaceholder"
              data-testid="album-input"
              name="album"
            />
          </FormRow>

          <FormRow :cols="2">
            <FormRow>
              <template #label>{{ t('songs.track') }}</template>
              <TextInput
                v-model="data.track"
                :placeholder="inputPlaceholder"
                data-testid="track-input"
                min="1"
                name="track"
                type="number"
              />
            </FormRow>
            <FormRow>
              <template #label>{{ t('songs.disc') }}</template>
              <TextInput
                v-model="data.disc"
                :placeholder="inputPlaceholder"
                data-testid="disc-input"
                min="1"
                name="disc"
                type="number"
              />
            </FormRow>
          </FormRow>

          <FormRow :cols="2">
            <FormRow>
              <template #label>{{ t('songs.genre') }}</template>
              <TextInput
                v-model="data.genre"
                :placeholder="inputPlaceholder"
                data-testid="genre-input"
                list="genres"
                name="genre"
              />
              <datalist id="genres">
                <option v-for="genre in genres" :key="genre" :value="genre" />
              </datalist>
            </FormRow>
            <FormRow>
              <template #label>{{ t('songs.year') }}</template>
              <TextInput
                v-model="data.year"
                :placeholder="inputPlaceholder"
                data-testid="year-input"
                name="year"
                type="number"
              />
            </FormRow>
          </FormRow>
        </TabPanel>

        <TabPanel
          v-if="editingOnlyOneSong"
          v-show="currentTab === 'lyrics'"
          id="editSongPanelLyrics"
          aria-labelledby="editSongTabLyrics"
        >
          <FormRow>
            <TextArea
              v-model="data.lyrics"
              v-koel-focus
              data-testid="lyrics-input"
              name="lyrics"
              :title="t('songs.lyrics')"
            />
          </FormRow>
        </TabPanel>
      </TabPanelContainer>
    </Tabs>

    <footer>
      <Btn type="submit">{{ t('ui.buttons.update') }}</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { eventBus } from '@/utils/eventBus'
import type { SongUpdateData, SongUpdateResult } from '@/stores/playableStore'
import { playableStore as songStore } from '@/stores/playableStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { genres } from '@/config/genres'
import { useForm } from '@/composables/useForm'
import { useBranding } from '@/composables/useBranding'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'

const { t } = useI18n()

const props = withDefaults(defineProps<{ songs: Song[], initialTab?: EditSongFormTabName }>(), {
  initialTab: 'details',
})

const emit = defineEmits<{ (e: 'close'): void }>()
const songs = props.songs
const currentTab = ref(props.initialTab)

const close = () => emit('close')

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { cover: defaultCover } = useBranding()

const editingOnlyOneSong = songs.length === 1
const editingMultipleSongs = !editingOnlyOneSong
const inputPlaceholder = editingMultipleSongs ? t('form.placeholders.leaveUnchanged') : ''

const allSongsShareSameValue = (key: keyof Song) => editingMultipleSongs
  ? new Set(songs.map(song => song[key])).size === 1
  : true

const allSongsAreFromSameArtist = allSongsShareSameValue('artist_name')
const allSongsAreInSameAlbum = allSongsShareSameValue('album_id')
const coverUrl = allSongsAreInSameAlbum ? (songs[0].album_cover || defaultCover) : defaultCover

const initialValues: SongUpdateData = {
  album_name: allSongsAreInSameAlbum ? songs[0].album_name : '',
  artist_name: allSongsAreFromSameArtist ? songs[0].artist_name : '',
  album_artist_name: '',
  track: allSongsShareSameValue('track') && songs[0].track !== 0 ? songs[0].track : null,
  disc: allSongsShareSameValue('disc') && songs[0].disc !== 0 ? songs[0].disc : null,
  year: allSongsShareSameValue('year') ? songs[0].year : null,
  genre: allSongsShareSameValue('genre') ? songs[0].genre : '',
  ...(editingOnlyOneSong
    ? {
        title: allSongsShareSameValue('title') ? songs[0].title : '',
        lyrics: editingOnlyOneSong ? songs[0].lyrics : '',
      }
    : {}),
}

if (allSongsAreInSameAlbum && allSongsAreFromSameArtist && songs[0].album_artist_id === songs[0].artist_id) {
  // If the album artist(s) is the same as the artist(s), we set the value as empty to not confuse the user
  // and make it less error-prone.
  initialValues.album_artist_name = ''
} else {
  initialValues.album_artist_name = allSongsShareSameValue('album_artist_name') ? songs[0].album_artist_name : ''
}

const inflect = () => {
  return songs.length === 1 ? t('messages.itemsSingular') : t('messages.itemsPlural')
}

const { data, isPristine, handleSubmit } = useForm<SongUpdateData>({
  initialValues,
  onSubmit: async data => await songStore.updateSongs(songs, data),
  onSuccess: (result: SongUpdateResult) => {
    toastSuccess(t('messages.itemsUpdated', { count: songs.length, item: inflect() }))
    eventBus.emit('SONGS_UPDATED', result)
    close()
  },
})

const displayedTitle = computed(() => editingOnlyOneSong ? data.title : t('songs.selected', { count: songs.length }))

const displayedArtistName = computed(() => {
  return allSongsAreFromSameArtist || data.artist_name ? data.artist_name : t('songs.mixedArtists')
})

const displayedAlbumName = computed(() => allSongsAreInSameAlbum || data.album_name ? data.album_name : t('songs.mixedAlbums'))

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('playlists.discardChanges'))) {
    close()
  }
}
</script>

<style lang="postcss" scoped>
.mixed {
  @apply text-k-fg-50;
}
</style>
