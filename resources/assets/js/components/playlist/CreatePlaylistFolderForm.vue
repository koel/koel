<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>New Playlist Folder</h1>
    </header>

    <main>
      <FormRow>
        <TextInput v-model="data.name" v-koel-focus name="name" placeholder="Folder name" required />
      </FormRow>
    </main>

    <footer>
      <Btn :disabled="loading" type="submit">Save</Btn>
      <Btn variant="neutral" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'close'): void }>()
const props = withDefaults(defineProps<{ parent?: PlaylistFolder | null }>(), {
  parent: null,
})

const close = () => emit('close')

const { data, loading, handleSubmit, maybeClose } = useForm<Pick<PlaylistFolder, 'name'>>({
  initialValues: {
    name: '',
  },
  toast: {
    loading: 'Creating folder…',
    success: (folder: PlaylistFolder) => `Playlist folder "${folder.name}" created.`,
  },
  onSubmit: async ({ name }) =>
    props.parent ? await playlistFolderStore.store(name, props.parent) : await playlistFolderStore.store(name),
  onSuccess: close,
  onClose: close,
})
</script>
