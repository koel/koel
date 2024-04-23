<template>
  <form class="max-w-[540px]" @submit.prevent="submit" @keydown.esc="maybeClose">
    <header class="gap-4">
      <img alt="" :src="coverUrl" class="w-[84px] aspect-square object-cover object-center rounded-md">
      <div class="flex-1 flex flex-col justify-center overflow-hidden">
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

    <Tabs class="mt-4">
      <TabList>
        <TabButton
          id="editSongTabDetails"
          :selected="currentTab === 'details'"
          aria-controls="editSongPanelDetails"
          @click="currentTab = 'details'"
        >
          Details
        </TabButton>
        <TabButton
          v-if="editingOnlyOneSong"
          id="editSongTabLyrics"
          :selected="currentTab === 'lyrics'"
          aria-controls="editSongPanelLyrics"
          data-testid="edit-song-lyrics-tab"
          @click="currentTab = 'lyrics'"
        >
          Lyrics
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
            <template #label>Title</template>
            <TextInput v-model="formData.title" v-koel-focus data-testid="title-input" name="title" title="Title" />
          </FormRow>

          <FormRow :cols="2">
            <FormRow>
              <template #label>Artist</template>
              <TextInput
                v-model="formData.artist_name"
                :placeholder="inputPlaceholder"
                data-testid="artist-input"
                name="artist"
              />
            </FormRow>

            <FormRow>
              <template #label>Album Artist</template>
              <TextInput
                v-model="formData.album_artist_name"
                :placeholder="inputPlaceholder"
                data-testid="albumArtist-input"
                name="album_artist"
              />
            </FormRow>
          </FormRow>

          <FormRow>
            <template #label>Album</template>
            <TextInput
              v-model="formData.album_name"
              :placeholder="inputPlaceholder"
              data-testid="album-input"
              name="album"
            />
          </FormRow>

          <FormRow :cols="2">
            <FormRow>
              <template #label>Track</template>
              <TextInput
                v-model="formData.track"
                :placeholder="inputPlaceholder"
                data-testid="track-input"
                min="1"
                name="track"
                type="number"
              />
            </FormRow>
            <FormRow>
              <template #label>Disc</template>
              <TextInput
                v-model="formData.disc"
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
              <template #label>Genre</template>
              <TextInput
                v-model="formData.genre"
                :placeholder="inputPlaceholder"
                data-testid="genre-input"
                name="genre"
                list="genres"
              />
              <datalist id="genres">
                <option v-for="genre in genres" :key="genre" :value="genre" />
              </datalist>
            </FormRow>
            <FormRow>
              <template #label>Year</template>
              <TextInput
                v-model="formData.year"
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
              v-model="formData.lyrics"
              v-koel-focus
              data-testid="lyrics-input"
              name="lyrics"
              title="Lyrics"
            />
          </FormRow>
        </TabPanel>
      </TabPanelContainer>
    </Tabs>

    <footer>
      <Btn type="submit">Update</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { computed, reactive, ref } from 'vue'
import { isEqual } from 'lodash'
import { defaultCover, eventBus, pluralize } from '@/utils'
import { songStore, SongUpdateData } from '@/stores'
import { useDialogBox, useErrorHandler, useMessageToaster, useModal, useOverlay } from '@/composables'
import { genres } from '@/config'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
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
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}
</script>

<style lang="postcss" scoped>
.mixed {
  @apply opacity-50;
}
</style>
