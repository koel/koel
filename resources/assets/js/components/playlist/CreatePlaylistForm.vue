<template>
  <div @keydown.esc="maybeClose">
    <SoundBars v-if="loading"/>
    <form v-else @submit.prevent="submit">
      <header>
        <h1>New Playlist</h1>
      </header>

      <main>
        <div class="form-row">
          <input
            v-model="name"
            v-koel-focus
            name="name"
            placeholder="Playlist name"
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
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { playlistStore } from '@/stores'
import { logger, requireInjection } from '@/utils'
import { useDialogBox, useMessageToaster } from '@/composables'
import { RouterKey } from '@/symbols'

import SoundBars from '@/components/ui/SoundBars.vue'
import Btn from '@/components/ui/Btn.vue'

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const router = requireInjection(RouterKey)

const loading = ref(false)
const name = ref('')

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const submit = async () => {
  loading.value = true

  try {
    const playlist = await playlistStore.store(name.value)
    close()
    toastSuccess(`Playlist "${playlist.name}" created.`)
    router.go(`playlist/${playlist.id}`)
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    loading.value = false
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
