<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Rename Playlist Folder</h1>
    </header>

    <main>
      <div class="form-row">
        <input
          v-model="name"
          v-koel-focus
          name="name"
          placeholder="Folder name"
          required
          title="Folder name"
          type="text"
        >
      </div>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { logger, requireInjection } from '@/utils'
import { playlistFolderStore } from '@/stores'
import { useDialogBox, useMessageToaster, useOverlay } from '@/composables'
import { PlaylistFolderKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const [folder, updateFolderName] = requireInjection(PlaylistFolderKey)

const name = ref(folder.value.name)

const submit = async () => {
  showOverlay()

  try {
    await playlistFolderStore.rename(folder.value, name.value)
    updateFolderName(name.value)
    toastSuccess('Playlist folder renamed.')
    close()
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (name.value.trim() === folder.value.name) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>
