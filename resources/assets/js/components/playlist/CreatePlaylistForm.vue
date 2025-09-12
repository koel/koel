<template>
  <form class="md:w-[480px] w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>
        New Playlist
        <span v-if="playables.length" class="text-k-text-secondary" data-testid="from-playables">
          from {{ pluralize(playables, entityName) }}
        </span>
      </h1>
    </header>

    <main>
      <div class="grid grid-cols-2 gap-4">
        <FormRow>
          <template #label>Name *</template>
          <TextInput v-model="data.name" v-koel-focus name="name" placeholder="Playlist name" required />
        </FormRow>
        <FormRow>
          <template #label>Folder</template>
          <SelectBox v-model="data.folder_id">
            <option :value="null" />
            <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
          </SelectBox>
        </FormRow>
        <FormRow class="col-span-2">
          <template #label>Description</template>
          <TextArea
            v-model="data.description"
            class="h-20"
            name="description"
            placeholder="Some optional description"
          />
        </FormRow>
        <div class="flex cols-span-2 gap-3 items-center">
          <span v-if="data.cover" class="w-24 h-24 aspect-square relative">
            <img :src="data.cover" alt="Cover" class="w-24 h-24 rounded object-cover">
            <button
              type="button"
              class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
              @click.prevent="data.cover = null"
            >
              Remove
            </button>
          </span>
          <div class="flex-1">
            <FileInput v-if="!data.cover" accept="image/*" name="cover" @change="onImageInputChange">
              Pick a cover (optional)
            </FileInput>
          </div>
        </div>
      </div>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { computed, toRef } from 'vue'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import type { CreatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { pluralize } from '@/utils/formatters'
import { useRouter } from '@/composables/useRouter'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useForm } from '@/composables/useForm'
import { useImageFileInput } from '@/composables/useImageFileInput'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import FileInput from '@/components/ui/form/FileInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, url } = useRouter()
const { getFromContext } = useModal<'CREATE_PLAYLIST_FORM'>()

const folders = toRef(playlistFolderStore.state, 'folders')
const targetFolder = getFromContext('folder')
const playables = getFromContext('playables')

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<CreatePlaylistData>({
  initialValues: {
    name: '',
    description: '',
    folder_id: targetFolder?.id ?? null,
    cover: null,
  },
  onSubmit: async data => await playlistStore.store(data, playables),
  onSuccess: (playlist: Playlist) => {
    close()
    toastSuccess(`Playlist "${playlist.name}" created.`)
    go(url('playlists.show', { id: playlist.id }))
  },
})

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (data.cover = dataUrl),
})

const entityName = computed(() => {
  switch (getPlayableCollectionContentType(playables)) {
    case 'songs':
      return 'song'
    case 'episodes':
      return 'song'
    default:
      return 'item'
  }
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
