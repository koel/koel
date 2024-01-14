<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Playlist</h1>
    </header>

    <main>
      <div class="form-row cols">
        <label class="name">
          Name
          <input
            v-model="name"
            v-koel-focus
            name="name"
            placeholder="Playlist name"
            required
            title="Playlist name"
            type="text"
          >
        </label>
        <label class="folder">
          Folder
          <select v-model="folderId">
            <option :value="null" />
            <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
          </select>
        </label>
      </div>
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

import Btn from '@/components/ui/Btn.vue'

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

<style lang="scss" scoped>
form {
  min-width: 100%;
}

label.folder {
  flex: .6;
}
</style>
