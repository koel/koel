<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>New Playlist Folder</h1>
    </header>

    <main>
      <div class="form-row">
        <input
          v-model="name"
          v-koel-focus
          name="name"
          placeholder="Folder name"
          required
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
import { playlistFolderStore } from '@/stores'
import { logger } from '@/utils'
import { useDialogBox, useMessageToaster, useOverlay } from '@/composables'

import Btn from '@/components/ui/Btn.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showErrorDialog, showConfirmDialog } = useDialogBox()

const name = ref('')

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const submit = async () => {
  showOverlay()

  try {
    const folder = await playlistFolderStore.store(name.value)
    close()
    toastSuccess(`Playlist folder "${folder.name}" created.`)
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}

const maybeClose = async () => {
  if (name.value.trim() === '') {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>
