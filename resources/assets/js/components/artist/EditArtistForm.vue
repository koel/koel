<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Artist</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Name</template>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          placeholder="Artist name"
          required
          title="Artist name"
        />
      </FormRow>
      <ArtworkField v-model="data.image">Pick an image (optional)</ArtworkField>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { cloneDeep, pick } from 'lodash'
import type { ArtistUpdateData } from '@/stores/artistStore'
import { artistStore } from '@/stores/artistStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { useForm } from '@/composables/useForm'

import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

const props = defineProps<{ artist: Artist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { artist } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<ArtistUpdateData>({
  initialValues: { ...pick(artist, 'name', 'image') },
  onSubmit: async data => {
    const formData = cloneDeep(data)

    if (formData.image === artist.image) {
      // If the image is the same, don't send it (the image URL) to the server.
      delete formData.image
    }

    await artistStore.update(artist, formData)
  },
  onSuccess: () => {
    toastSuccess('Artist updated.')
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
