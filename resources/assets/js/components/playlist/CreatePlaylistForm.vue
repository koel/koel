<template>
  <form class="md:w-[480px] w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>
        New Playlist
        <span v-if="playables.length" data-testid="from-playables">from {{ pluralize(playables, entityName) }}</span>
      </h1>
    </header>

    <main>
      <PlaylistDetailsFields
        v-model:name="data.name"
        v-model:folder-id="data.folder_id"
        v-model:folder-name="data.folder_name"
        v-model:description="data.description"
        v-model:cover="data.cover"
      />
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn variant="ghost" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import type { CreatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { pluralize } from '@/utils/formatters'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import PlaylistDetailsFields from '@/components/playlist/PlaylistDetailsFields.vue'

const props = withDefaults(defineProps<{ playables?: Playable[]; folder?: PlaylistFolder | null }>(), {
  playables: () => [],
})

const emit = defineEmits<{ (e: 'close'): void }>()

const { playables, folder: targetFolder } = props

const { toastSuccess } = useMessageToaster()
const { go, url } = useRouter()

const close = () => emit('close')

const { data, handleSubmit, maybeClose } = useForm<CreatePlaylistData>({
  initialValues: {
    name: '',
    description: '',
    folder_id: targetFolder?.id ?? null,
    folder_name: null,
    cover: null,
  },
  onSubmit: async data => await playlistStore.store(data, playables),
  onSuccess: (playlist: Playlist) => {
    close()
    toastSuccess(`Playlist "${playlist.name}" created.`)
    go(url('playlists.show', { id: playlist.id }))
  },
  onClose: close,
})

const entityName = computed(() => {
  switch (getPlayableCollectionContentType(playables)) {
    case 'songs':
      return 'song'
    case 'episodes':
      return 'episode'
    default:
      return 'item'
  }
})
</script>
