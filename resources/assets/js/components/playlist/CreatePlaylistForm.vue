<template>
  <form class="md:w-[480px] w-full" @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>
        New Playlist
        <span v-if="songs.length" data-testid="from-songs" class="text-k-text-secondary">
          from {{ pluralize(songs, 'song') }}
        </span>
      </h1>
    </header>

    <main>
      <FormRow :cols="2">
        <FormRow>
          <template #label>Name</template>
          <TextInput v-model="name" v-koel-focus name="name" placeholder="Playlist name" required />
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
import { playlistFolderStore, playlistStore } from '@/stores'
import { pluralize } from '@/utils'
import { useDialogBox, useErrorHandler, useMessageToaster, useModal, useOverlay, useRouter } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go } = useRouter()
const { getFromContext } = useModal()

const targetFolder = getFromContext<PlaylistFolder | null>('folder') ?? null
const songs = getFromContext<Song[]>('songs') ?? []

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
    }, songs)

    close()
    toastSuccess(`Playlist "${playlist.name}" created.`)
    go(`playlist/${playlist.id}`)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
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
