<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Playlist</h1>
    </header>

    <main>
      <FormRow :cols="2">
        <FormRow>
          <template #label>Name</template>
          <TextInput
            v-model="name"
            v-koel-focus
            name="name"
            placeholder="Playlist name"
            required
            title="Playlist name"
          />
        </FormRow>
        <FormRow>
          <template #label>Folder</template>
          <SelectBox v-model="folderId">
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
import { ref, toRef } from 'vue'
import { logger } from '@/utils'
import { playlistFolderStore, playlistStore } from '@/stores'
import { useDialogBox, useMessageToaster, useModal, useOverlay } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const playlist = useModal().getFromContext<Playlist>('playlist')

const name = ref(playlist.name)
const folderId = ref(playlist.folder_id)
const folders = toRef(playlistFolderStore.state, 'folders')

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const submit = async () => {
  showOverlay()

  try {
    await playlistStore.update(playlist, {
      name: name.value,
      folder_id: folderId.value
    })

    toastSuccess('Playlist updated.')
    close()
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}

const isPristine = () => playlist.name === name.value && playlist.folder_id === folderId.value

const maybeClose = async () => {
  if (isPristine()) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>

<style lang="postcss" scoped>
form {
  min-width: 100%;
}

label.folder {
  flex: .6;
}
</style>
