<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Playlist</h1>
    </header>

    <main>
      <FormRow :cols="2">
        <FormRow>
          <template #label>Name</template>
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
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { pick } from 'lodash'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import type { UpdatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const folders = toRef(playlistFolderStore.state, 'folders')
const playlist = useModal().getFromContext<Playlist>('playlist')

const { data, isPristine, handleSubmit } = useForm<UpdatePlaylistData>({
  initialValues: pick(playlist, 'name', 'folder_id'),
  onSubmit: async ({ name, folder_id }) => await playlistStore.update(playlist, { name, folder_id }),
  onSuccess: () => {
    toastSuccess('Playlist updated.')
    close()
  },
})

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
