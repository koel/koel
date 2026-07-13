<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Playlist Folder</h1>
    </header>

    <main class="flex flex-col gap-4">
      <FormRow>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          placeholder="Folder name"
          required
          title="Folder name"
        />
      </FormRow>
      <FormRow>
        <template #label>Parent Folder</template>
        <SelectBox v-model="data.parent_id">
          <option :value="null">Root</option>
          <option v-for="parent in parentFolders" :key="parent.id" :value="parent.id">
            {{ playlistFolderStore.pathFor(parent) }}
          </option>
        </SelectBox>
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn variant="ghost" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { orderBy, pick } from 'lodash-es'
import { computed } from 'vue'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const props = defineProps<{ folder: PlaylistFolder }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { folder } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const parentFolders = computed(() => {
  const unavailableParentIds = new Set([
    folder.id,
    ...playlistFolderStore.descendantsOf(folder).map(descendant => descendant.id),
  ])

  return orderBy(
    playlistFolderStore.state.folders.filter(candidate => !unavailableParentIds.has(candidate.id)),
    candidate => playlistFolderStore.pathFor(candidate),
  )
})

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<Pick<PlaylistFolder, 'name' | 'parent_id'>>({
  initialValues: pick(folder, 'name', 'parent_id'),
  onSubmit: async changes => await playlistFolderStore.update(folder, changes),
  onSuccess: () => {
    toastSuccess('Playlist folder updated.')
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || (await showConfirmDialog('Discard all changes?'))) {
    close()
  }
}
</script>
