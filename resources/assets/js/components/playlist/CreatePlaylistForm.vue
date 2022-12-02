<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>New Playlist</h1>
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
import { playlistFolderStore, playlistStore } from '@/stores'
import { logger } from '@/utils'
import { useDialogBox, useMessageToaster, useModal, useOverlay, useRouter } from '@/composables'

import Btn from '@/components/ui/Btn.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const { go } = useRouter()
const targetFolder = useModal().getFromContext<PlaylistFolder | null>('folder')

const folderId = ref(targetFolder?.id)
const name = ref('')
const folders = toRef(playlistFolderStore.state, 'folders')

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const submit = async () => {
  showOverlay()

  try {
    const playlist = await playlistStore.store(name.value, {
      folder_id: folderId.value
    })

    close()
    toastSuccess(`Playlist "${playlist.name}" created.`)
    go(`playlist/${playlist.id}`)
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}

const isPristine = () => name.value.trim() === '' && folderId.value === targetFolder?.id

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
  width: 540px;
}

label.folder {
  flex: .6;
}
</style>
