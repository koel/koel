<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('artists.edit') }}</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>{{ t('artists.name') }}</template>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          :placeholder="t('artists.artistName')"
          required
          :title="t('artists.artistName')"
        />
      </FormRow>
      <ArtworkField v-model="data.image">{{ t('artists.pickImage') }}</ArtworkField>
    </main>

    <footer>
      <Btn type="submit">{{ t('auth.save') }}</Btn>
      <Btn white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { cloneDeep, pick } from 'lodash'
import type { ArtistUpdateData } from '@/stores/artistStore'
import { artistStore } from '@/stores/artistStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { useForm } from '@/composables/useForm'

import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

const { t } = useI18n()

const props = defineProps<{ artist: Artist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { artist } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<ArtistUpdateData>({
  initialValues: { ...pick(artist, 'name', 'image') },
  onSubmit: async data => {
    const formData = cloneDeep(data)

    if (formData.image === artist.image) {
      // If the image is the same, don't send it (the image URL) to the server.
      delete formData.image
    }

    await artistStore.update(artist, formData)
  },
  onSuccess: () => {
    toastSuccess(t('artists.updated'))
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('artists.discardChanges'))) {
    close()
  }
}
</script>
