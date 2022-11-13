<template>
  <div @keydown.esc="maybeClose">
    <SoundBars v-if="loading"/>
    <form v-else data-testid="edit-playlist-folder-form" @submit.prevent="submit">
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
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { logger, requireInjection } from '@/utils'
import { playlistFolderStore } from '@/stores'
import { DialogBoxKey, MessageToasterKey, PlaylistFolderKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'
import SoundBars from '@/components/ui/SoundBars.vue'

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)
const [folder, updateFolderName] = requireInjection(PlaylistFolderKey)

const name = ref(folder.value.name)
const loading = ref(false)

const submit = async () => {
  loading.value = true

  try {
    await playlistFolderStore.rename(folder.value, name.value)
    updateFolderName(name.value)
    toaster.value.success('Playlist folder renamed.')
    close()
  } catch (error) {
    dialog.value.error('Something went wrong. Please try again.')
    logger.error(error)
  } finally {
    loading.value = false
  }
}

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (name.value.trim() === folder.value.name) {
    close()
    return
  }

  await dialog.value.confirm('Discard all changes?') && close()
}
</script>
