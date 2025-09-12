<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Radio Station</h1>
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
      <div class="flex gap-3 items-center">
        <span v-if="displayedLogo" class="w-24 h-24 aspect-square relative">
          <img :src="displayedLogo" alt="Logo" class="w-24 h-24 rounded object-cover">
          <button
            type="button"
            class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
            @click.prevent="removeOrResetLogo"
          >
            Remove
          </button>
        </span>
        <div class="flex-1">
          <FileInput v-if="!displayedLogo" accept="image/*" name="logo" @change="onImageInputChange">
            Pick a logo (optional)
          </FileInput>
        </div>
      </div>
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
import { pick } from 'lodash'
import { computed } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import type { RadioStationData } from '@/stores/radioStationStore'
import { radioStationStore } from '@/stores/radioStationStore'
import { useModal } from '@/composables/useModal'
import { useForm } from '@/composables/useForm'
import { useImageFileInput } from '@/composables/useImageFileInput'

import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import FileInput from '@/components/ui/form/FileInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const station = useModal<'EDIT_RADIO_STATION_FORM'>().getFromContext('station')

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const { data, isPristine, handleSubmit } = useForm<RadioStationData>({
  initialValues: {
    ...pick(station, 'name', 'url', 'description', 'is_public'),
    logo: null,
  },
  onSubmit: async data => await radioStationStore.update(station, data),
  onSuccess: () => {
    close()
    toastSuccess('Station updated.')
  },
})

const displayedLogo = computed(() => station.logo || data.logo)

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (data.logo = dataUrl),
})

const removeOrResetLogo = async () => {
  if (data.logo) {
    data.logo = null
  } else if (station.logo && await showConfirmDialog('Remove the logo? This cannot be undone.')) {
    await radioStationStore.removeLogo(station)
    station.logo = null // technically not needed but useful during testing
  }
}

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
