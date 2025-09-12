<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Playlist</h1>
    </header>

    <main>
      <div class="grid grid-cols-2 gap-4">
        <FormRow>
          <template #label>Name *</template>
          <TextInput
            v-model="data.name"
            v-koel-focus
            name="name"
            placeholder="Playlist name"
            required
            title="Playlist name"
          />
        </FormRow>
        <FormRow>
          <template #label>Folder</template>
          <SelectBox v-model="data.folder_id">
            <option :value="null" />
            <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
          </SelectBox>
        </FormRow>
        <FormRow class="col-span-2">
          <template #label>Description</template>
          <TextArea v-model="data.description" class="h-28" name="description" />
        </FormRow>
        <div class="cols-span-2 flex gap-3 items-center">
          <span v-if="displayedCover" class="w-24 h-24 aspect-square relative">
            <img :src="displayedCover" alt="Cover" class="w-24 h-24 rounded object-cover">
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
      </div>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { computed, toRef } from 'vue'
import { pick } from 'lodash'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import type { UpdatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useForm } from '@/composables/useForm'
import { useImageFileInput } from '@/composables/useImageFileInput'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import FileInput from '@/components/ui/form/FileInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const folders = toRef(playlistFolderStore.state, 'folders')
const playlist = useModal<'EDIT_PLAYLIST_FORM'>().getFromContext('playlist')

const { data, isPristine, handleSubmit } = useForm<UpdatePlaylistData>({
  initialValues: {
    ...pick(playlist, 'name', 'folder_id', 'description'),
    cover: null,
  },
  onSubmit: async data => await playlistStore.update(playlist, data),
  onSuccess: () => {
    toastSuccess('Playlist updated.')
    close()
  },
})

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (data.cover = dataUrl),
})

const displayedCover = computed(() => playlist.cover || data.cover)

const removeOrResetCover = async () => {
  if (data.cover) {
    data.cover = null
  } else if (playlist.cover && await showConfirmDialog('Remove the cover? This cannot be undone.')) {
    await playlistStore.removeCover(playlist)
    playlist.cover = null // technically not needed but useful during testing
  }
}

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>

<style lang="postcss" scoped>
form {
  min-width: 100%;
}

label.folder {
  flex: 0.6;
}
</style>
