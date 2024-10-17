<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Rename Playlist Folder</h1>
    </header>

    <main>
      <FormRow>
        <TextInput v-model="name" v-koel-focus name="name" placeholder="Folder name" required title="Folder name" />
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useOverlay } from '@/composables/useOverlay'
import { useModal } from '@/composables/useModal'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const folder = useModal().getFromContext<PlaylistFolder>('folder')

const name = ref(folder.name)

const close = () => emit('close')

const submit = async () => {
  showOverlay()

  try {
    await playlistFolderStore.rename(folder, name.value)
    toastSuccess('Playlist folder renamed.')
    close()
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}

const maybeClose = async () => {
  if (name.value.trim() === folder.name) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>
