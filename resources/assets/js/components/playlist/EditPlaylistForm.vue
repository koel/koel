<template>
  <div @keydown.esc="maybeClose">
    <SoundBars v-if="loading"/>
    <form v-else data-testid="edit-playlist-form" @submit.prevent="submit">
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
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { logger, requireInjection } from '@/utils'
import { playlistStore } from '@/stores'
import { useDialogBox, useMessageToaster } from '@/composables'
import { PlaylistKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'
import SoundBars from '@/components/ui/SoundBars.vue'

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const [playlist, updatePlaylistName] = requireInjection(PlaylistKey)

const name = ref(playlist.value.name)
const loading = ref(false)

const submit = async () => {
  loading.value = true

  try {
    await playlistStore.update(playlist.value, { name: name.value })
    updatePlaylistName(name.value)
    toastSuccess('Playlist renamed.')
    close()
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.')
    logger.error(error)
  } finally {
    loading.value = false
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
