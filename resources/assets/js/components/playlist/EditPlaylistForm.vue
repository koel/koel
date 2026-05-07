<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Playlist</h1>
    </header>

    <main>
      <div class="grid grid-cols-2 gap-4">
        <FormRow>
          <template #label>Name *</template>
          <TextInput v-model="data.name" v-koel-focus name="name" placeholder="Playlist name" required />
        </FormRow>
        <FormRow>
          <template #label>Folder</template>
          <FolderSelect v-model:folder-id="data.folder_id" v-model:folder-name="data.folder_name" />
        </FormRow>
        <FormRow class="col-span-2">
          <template #label>Description</template>
          <TextArea v-model="data.description" class="h-28" name="description" />
        </FormRow>
        <ArtworkField v-model="data.cover">Pick or paste a cover (optional)</ArtworkField>
      </div>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn variant="ghost" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { pick } from 'lodash-es'
import { toRaw } from 'vue'

import type { UpdatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import FolderSelect from '@/components/ui/form/FolderSelect.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

const props = defineProps<{ playlist: Playlist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { playlist } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<UpdatePlaylistData>({
  initialValues: { ...pick(playlist, 'name', 'folder_id', 'description', 'cover'), folder_name: null },
  onSubmit: async data => {
    const formData = structuredClone(toRaw(data))

    if (formData.cover === playlist.cover) {
      delete formData.cover
    }

    await playlistStore.update(playlist, formData)
  },
  onSuccess: () => {
    toastSuccess('Playlist updated.')
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || (await showConfirmDialog('Discard all changes?'))) {
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
