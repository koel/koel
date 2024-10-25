<template>
  <form class="md:w-[480px] w-full" @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>
        New Playlist
        <span v-if="playables.length" class="text-k-text-secondary" data-testid="from-playables">
          from {{ pluralize(playables, noun) }}
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
import { computed, ref, toRef } from 'vue'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { pluralize } from '@/utils/formatters'
import { useRouter } from '@/composables/useRouter'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useOverlay } from '@/composables/useOverlay'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, url } = useRouter()
const { getFromContext } = useModal()

const targetFolder = getFromContext<PlaylistFolder | null>('folder') ?? null
const playables = getFromContext<Playable[]>('playables') ?? []

const folderId = ref(targetFolder?.id)
const name = ref('')
const folders = toRef(playlistFolderStore.state, 'folders')

const close = () => emit('close')

const noun = computed(() => {
  switch (getPlayableCollectionContentType(playables)) {
    case 'songs':
      return 'song'
    case 'episodes':
      return 'song'
    default:
      return 'item'
  }
})

const submit = async () => {
  showOverlay()

  try {
    const playlist = await playlistStore.store(name.value, {
      folder_id: folderId.value,
    }, playables)

    close()
    toastSuccess(`Playlist "${playlist.name}" created.`)
    go(url('playlists.show', { id: playlist.id }))
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
