<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('radio.edit') }}</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>{{ t('radio.name') }}</template>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          :placeholder="t('radio.namePlaceholder')"
          required
        />
      </FormRow>
      <FormRow>
        <template #label>{{ t('radio.url') }}</template>
        <TextInput
          v-model="data.url"
          type="url"
          name="url"
          :placeholder="t('radio.urlPlaceholder')"
          required
        />
      </FormRow>
      <FormRow>
        <template #label>{{ t('radio.description') }}</template>
        <TextArea
          v-model="data.description"
          name="description"
          class="max-h-24"
          :placeholder="t('radio.descriptionPlaceholder')"
        />
      </FormRow>
      <ArtworkField v-model="data.logo">{{ t('radio.pickLogo') }}</ArtworkField>
      <FormRow>
        <label>
          <CheckBox v-model="data.is_public" name="is_public" />
          <span class="ml-2">{{ t('radio.makePublic') }}</span>
        </label>
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">{{ t('auth.save') }}</Btn>
      <Btn white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { cloneDeep, pick } from 'lodash'
import { useI18n } from 'vue-i18n'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import type { RadioStationData } from '@/stores/radioStationStore'
import { radioStationStore } from '@/stores/radioStationStore'
import { useForm } from '@/composables/useForm'

import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

const props = defineProps<{ station: RadioStation }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { station } = props

const { t } = useI18n()
const close = () => emit('close')

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const { data, isPristine, handleSubmit } = useForm<RadioStationData>({
  initialValues: { ...pick(station, 'name', 'url', 'description', 'is_public', 'logo') },
  onSubmit: async data => {
    const formData = cloneDeep(data)

    if (formData.logo === station.logo) {
      delete formData.logo
    }

    await radioStationStore.update(station, formData)
  },
  onSuccess: () => {
    close()
    toastSuccess(t('radio.updated'))
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('radio.discardChanges'))) {
    close()
  }
}
</script>
