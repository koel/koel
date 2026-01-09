<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('playlists.folder.new') }}</h1>
    </header>

    <main>
      <FormRow>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          :placeholder="t('playlists.folder.name')"
          required
        />
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">{{ t('auth.save') }}</Btn>
      <Btn white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { t } = useI18n()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<Pick<PlaylistFolder, 'name'>>({
  initialValues: {
    name: '',
  },
  onSubmit: async ({ name }) => await playlistFolderStore.store(name),
  onSuccess: (folder: PlaylistFolder) => {
    close()
    toastSuccess(t('playlists.folder.created', { name: folder.name }))
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('playlists.folder.discardChanges'))) {
    close()
  }
}
</script>
