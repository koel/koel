<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Playlist</h1>
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
      <Btn :disabled="loading" type="submit">Save</Btn>
      <Btn variant="neutral" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { pick } from 'lodash-es'
import { toRaw } from 'vue'

import type { UpdatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import PlaylistDetailsFields from '@/components/playlist/PlaylistDetailsFields.vue'

const props = defineProps<{ playlist: Playlist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { playlist } = props

const close = () => emit('close')

const { data, loading, handleSubmit, maybeClose } = useForm<UpdatePlaylistData>({
  initialValues: { ...pick(playlist, 'name', 'folder_id', 'description', 'cover'), folder_name: null },
  toast: {
    loading: 'Saving playlist…',
    success: 'Playlist updated.',
  },
  onSubmit: async data => {
    const formData = structuredClone(toRaw(data))

    if (formData.cover === playlist.cover) {
      delete formData.cover
    }

    await playlistStore.update(playlist, formData)
  },
  onSuccess: close,
  onClose: close,
})
</script>

<style lang="postcss" scoped>
form {
  min-width: 100%;
}

label.folder {
  flex: 0.6;
}
</style>
