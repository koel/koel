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
      <div class="flex gap-3 items-center">
        <span v-if="displayedImage" class="w-24 h-24 aspect-square relative">
          <img :src="displayedImage" alt="Artist image" class="w-24 h-24 rounded object-cover">
          <button
            type="button"
            class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
            @click.prevent="removeOrResetImage"
          >
            Remove
          </button>
        </span>
        <div class="flex-1">
          <FileInput v-if="!displayedImage" accept="image/*" name="image" @change="onImageInputChange">
            Pick an image (optional)
          </FileInput>
        </div>
      </div>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { useModal } from '@/composables/useModal'
import type { ArtistUpdateData } from '@/stores/artistStore'
import { artistStore } from '@/stores/artistStore'
import { useForm } from '@/composables/useForm'
import { useImageFileInput } from '@/composables/useImageFileInput'

import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FileInput from '@/components/ui/form/FileInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const artist = useModal<'EDIT_ARTIST_FORM'>().getFromContext('artist')

const { data, isPristine, handleSubmit } = useForm<ArtistUpdateData>({
  initialValues: {
    name: artist.name,
    image: '',
  },
  onSubmit: async data => await artistStore.update(artist, data),
  onSuccess: () => {
    toastSuccess('Artist updated.')
    close()
  },
})

const displayedImage = computed(() => artist.image || data.image)

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (data.image = dataUrl),
})

const removeOrResetImage = async () => {
  if (data.image) {
    data.image = ''
  } else if (artist.image && await showConfirmDialog('Remove the artist image? This cannot be undone.')) {
    await artistStore.removeImage(artist)
    artist.image = '' // technically not needed but useful during testing
  }
}

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
