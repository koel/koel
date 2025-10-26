<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>New Radio Station</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Name</template>
        <TextInput
          v-model="data.name"
          v-koel-focus
          name="name"
          placeholder="My Favorite Radio Station"
          required
        />
      </FormRow>
      <FormRow>
        <template #label>URL</template>
        <TextInput
          v-model="data.url"
          type="url"
          name="url"
          placeholder="https://radio.example.com/stream"
          required
        />
      </FormRow>
      <FormRow>
        <template #label>Description</template>
        <TextArea
          v-model="data.description"
          name="description"
          class="max-h-24"
          placeholder="A short description of the station"
        />
      </FormRow>
      <ArtworkField v-model="data.logo">Pick a logo (optional)</ArtworkField>
      <FormRow>
        <label>
          <CheckBox v-model="data.is_public" name="is_public" />
          <span class="ml-2">Make this station public</span>
        </label>
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
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

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<RadioStationData>({
  initialValues: {
    name: '',
    logo: null,
    url: '',
    description: '',
    is_public: false,
  },
  onSubmit: async data => await radioStationStore.store(data),
  onSuccess: (station: RadioStation) => {
    close()
    toastSuccess(`Station "${station.name}" added.`)
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
