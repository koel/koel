<template>
  <form class="md:w-[480px] w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>
        New Playlist
        <span v-if="playables.length" data-testid="from-playables">from {{ pluralize(playables, entityName) }}</span>
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
            <option v-for="{ id, name } in folders" :key="id" :value="id">{{ name }}</option>
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
        <ArtworkField v-model="data.cover">Pick a cover (optional)</ArtworkField>
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
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

const props = withDefaults(defineProps<{ playables: Playable[], folder?: PlaylistFolder | null }>(), {
  playables: () => [],
})

const emit = defineEmits<{ (e: 'close'): void }>()

const { playables, folder: targetFolder } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, url } = useRouter()

const folders = toRef(playlistFolderStore.state, 'folders')

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
