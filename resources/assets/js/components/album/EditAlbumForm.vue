<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Album</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Name</template>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          placeholder="Album name"
          required
          title="Album name"
        />
      </FormRow>
      <div class="grid grid-cols-2 gap-2">
        <FormRow>
          <template #label>Artist</template>
          <TextInput
            v-model="album.artist_name"
            name="artist"
            disabled
            title="Artist name cannot be changed"
          />
        </FormRow>
        <FormRow>
          <template #label>Release year</template>
          <TextInput
            v-model="data.year"
            type="number"
            name="year"
            title="Release year"
            min="1000"
          />
        </FormRow>
      </div>
      <ArtworkField v-model="data.cover">Pick a cover (optional)</ArtworkField>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
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
    toastSuccess('Album updated.')
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
