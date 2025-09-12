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
      <div class="flex gap-3 items-center">
        <span v-if="displayedCover" class="w-24 h-24 aspect-square relative">
          <img :src="displayedCover" alt="Album cover" class="w-24 h-24 rounded object-cover">
          <button
            type="button"
            class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
            @click.prevent="removeOrResetCover"
          >
            Remove
          </button>
        </span>
        <div class="flex-1">
          <FileInput v-if="!displayedCover" accept="image/*" name="cover" @change="onImageInputChange">
            Pick a cover (optional)
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
import { pick } from 'lodash'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { useModal } from '@/composables/useModal'
import type { AlbumUpdateData } from '@/stores/albumStore'
import { albumStore } from '@/stores/albumStore'
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

const album = useModal<'EDIT_ALBUM_FORM'>().getFromContext('album')

const { data, isPristine, handleSubmit } = useForm<AlbumUpdateData>({
  initialValues: {
    ...pick(album, 'name', 'year'),
    cover: '',
  },
  onSubmit: async data => await albumStore.update(album, data),
  onSuccess: () => {
    toastSuccess('Album updated.')
    close()
  },
})

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (data.cover = dataUrl),
})

const displayedCover = computed(() => album.cover || data.cover)

const removeOrResetCover = async () => {
  if (data.cover) {
    data.cover = ''
  } else if (album.cover && await showConfirmDialog('Remove the album cover? This cannot be undone.')) {
    await albumStore.removeCover(album)
    album.cover = '' // technically not needed but useful during testing
  }
}

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
