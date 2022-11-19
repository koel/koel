<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Rename Playlist</h1>
    </header>

    <main>
      <div class="form-row">
        <input
          v-model="name"
          v-koel-focus
          name="name"
          placeholder="Playlist name"
          required
          title="Playlist name"
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
import { playlistStore } from '@/stores'
import { useDialogBox, useMessageToaster, useOverlay } from '@/composables'
import { PlaylistKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const [playlist, updatePlaylistName] = requireInjection(PlaylistKey)

const name = ref(playlist.value.name)

const submit = async () => {
  showOverlay()

  try {
    await playlistStore.update(playlist.value, { name: name.value })
    updatePlaylistName(name.value)
    toastSuccess('Playlist renamed.')
    close()
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (name.value.trim() === playlist.value.name) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>
