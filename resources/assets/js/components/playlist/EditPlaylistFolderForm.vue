<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Rename Playlist Folder</h1>
    </header>

    <main>
      <FormRow>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          placeholder="Folder name"
          required
          title="Folder name"
        />
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { pick } from 'lodash'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const folder = useModal<'EDIT_PLAYLIST_FOLDER_FORM'>().getFromContext('folder')

const { data, isPristine, handleSubmit } = useForm<Pick<PlaylistFolder, 'name'>>({
  initialValues: pick(folder, 'name'),
  onSubmit: async ({ name }) => await playlistFolderStore.rename(folder, name),
  onSuccess: () => {
    toastSuccess('Playlist folder renamed.')
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
