<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>New Radio Station</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Name</template>
        <TextInput
          v-model="newStation.name"
          v-koel-focus
          name="name"
          placeholder="My Favorite Radio Station"
          required
        />
      </FormRow>
      <FormRow>
        <template #label>URL</template>
        <TextInput
          v-model="newStation.url"
          type="url"
          name="url"
          placeholder="https://radio.example.com/stream"
          required
        />
      </FormRow>
      <FormRow>
        <template #label>Description</template>
        <TextArea
          v-model="newStation.description"
          name="description"
          class="max-h-24"
          placeholder="A short description of the station"
        />
      </FormRow>
      <div class="flex gap-3 items-center">
        <span v-if="newStation.logo" class="w-24 h-24 aspect-square relative">
          <img :src="newStation.logo" alt="Logo" class="w-24 h-24 rounded object-cover">
          <button
            type="button"
            class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
            @click.prevent="newStation.logo = null"
          >
            Remove
          </button>
        </span>
        <div class="flex-1">
          <FileInput v-if="!newStation.logo" accept="image/*" name="logo" @change="onLogoChange">
            Pick a logo (optional)
          </FileInput>
        </div>
      </div>
      <FormRow>
        <label>
          <CheckBox v-model="newStation.is_public" name="is_public" />
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
import { isEqual } from 'lodash'
import { reactive } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useOverlay } from '@/composables/useOverlay'
import { useErrorHandler } from '@/composables/useErrorHandler'
import type { RadioStationData } from '@/stores/radioStationStore'
import { radioStationStore } from '@/stores/radioStationStore'
import { useFileReader } from '@/composables/useFileReader'

import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import FileInput from '@/components/ui/form/FileInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { readAsDataUrl } = useFileReader()

const emptyStationData: RadioStationData = {
  name: '',
  logo: null,
  url: '',
  description: '',
  is_public: false,
}

const newStation = reactive<RadioStationData>(Object.assign({}, emptyStationData))

const onLogoChange = (e: InputEvent) => {
  const target = e.target as HTMLInputElement

  if (!target.files || !target.files.length) {
    return
  }

  readAsDataUrl(target.files[0], data => {
    newStation.logo = data
  })

  // reset the value so that, if the user removes the logo, they can re-pick the same one
  target.value = ''
}

const close = () => emit('close')

const submit = async () => {
  showOverlay()

  try {
    const station = await radioStationStore.store(newStation)
    close()
    toastSuccess(`Station "${station.name}" added.`)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}

const maybeClose = async () => {
  if (isEqual(newStation, emptyStationData)) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>
