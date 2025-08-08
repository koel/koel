<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Radio Station</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Name</template>
        <TextInput
          v-model="updateData.name"
          v-koel-focus
          name="name"
          placeholder="My Favorite Radio Station"
          required
        />
      </FormRow>
      <FormRow>
        <template #label>URL</template>
        <TextInput
          v-model="updateData.url"
          type="url"
          name="url"
          placeholder="https://radio.example.com/stream"
          required
        />
      </FormRow>
      <FormRow>
        <template #label>Description</template>
        <TextArea
          v-model="updateData.description"
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
          <FileInput v-if="!displayedLogo" accept="image/*" name="logo" @change="onLogoChange">
            Pick a logo (optional)
          </FileInput>
        </div>
      </div>
      <FormRow>
        <label>
          <CheckBox v-model="updateData.is_public" name="is_public" />
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
import { isEqual, pick } from 'lodash'
import type { Reactive } from 'vue'
import { computed, reactive } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useOverlay } from '@/composables/useOverlay'
import { useErrorHandler } from '@/composables/useErrorHandler'
import type { RadioStationData } from '@/stores/radioStationStore'
import { radioStationStore } from '@/stores/radioStationStore'
import { useFileReader } from '@/composables/useFileReader'
import { useModal } from '@/composables/useModal'

import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import FileInput from '@/components/ui/form/FileInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const station = useModal().getFromContext<Reactive<RadioStation>>('station')
const updateData = reactive<RadioStationData>({
  ...pick(station, 'name', 'url', 'description', 'is_public'),
  logo: null,
})

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { readAsDataUrl } = useFileReader()

const displayedLogo = computed(() => station.logo || updateData.logo)

const onLogoChange = (e: InputEvent) => {
  const target = e.target as HTMLInputElement

  if (!target.files || !target.files.length) {
    updateData.logo = null
    return
  }

  readAsDataUrl(target.files[0], data => {
    updateData.logo = data
  })

  // reset the value so that, if the user removes the logo, they can re-pick the same one
  target.value = ''
}

const close = () => emit('close')

const removeOrResetLogo = async () => {
  if (updateData.logo) {
    updateData.logo = null
  } else if (station.logo && await showConfirmDialog('Remove the logo? This cannot be undone.')) {
    await radioStationStore.removeLogo(station)
    station.logo = null // technically not needed but useful during testing
  }
}

const submit = async () => {
  showOverlay()

  try {
    await radioStationStore.update(station, updateData)
    close()
    toastSuccess(`Station updated.`)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}

const isPristine = () => isEqual(updateData, {
  ...pick(station, 'name', 'url', 'description', 'is_public'),
  logo: null,
})

const maybeClose = async () => {
  if (isPristine()) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>
