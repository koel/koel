<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('albums.edit') }}</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>{{ t('albums.name') }}</template>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          :placeholder="t('albums.albumName')"
          required
          :title="t('albums.albumName')"
        />
      </FormRow>
      <div class="grid grid-cols-2 gap-2">
        <FormRow>
          <template #label>{{ t('albums.artist') }}</template>
          <TextInput
            v-model="album.artist_name"
            name="artist"
            disabled
            :title="t('albums.artistNotChangeable')"
          />
        </FormRow>
        <FormRow>
          <template #label>{{ t('albums.releaseYear') }}</template>
          <TextInput
            v-model="data.year"
            type="number"
            name="year"
            :title="t('albums.releaseYear')"
            min="1000"
          />
        </FormRow>
      </div>
      <ArtworkField v-model="data.cover">{{ t('albums.pickCover') }}</ArtworkField>
    </main>

    <footer>
      <Btn type="submit">{{ t('auth.save') }}</Btn>
      <Btn white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { cloneDeep, pick } from 'lodash'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import type { AlbumUpdateData } from '@/stores/albumStore'
import { albumStore } from '@/stores/albumStore'
import { useForm } from '@/composables/useForm'

import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

const { t } = useI18n()

const props = defineProps<{ album: Album }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { album } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<AlbumUpdateData>({
  initialValues: { ...pick(album, 'name', 'year', 'cover') },
  onSubmit: async data => {
    const formData = cloneDeep(data)

    if (formData.cover === album.cover) {
      // If the image is the same, don't send it (the image URL) to the server.
      delete formData.cover
    }

    await albumStore.update(album, formData)
  },
  onSuccess: () => {
    toastSuccess(t('albums.updated'))
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('albums.discardChanges'))) {
    close()
  }
}
</script>
