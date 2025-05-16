<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Album</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Name</template>
        <TextInput
          v-model="name"
          v-koel-focus
          name="name"
          placeholder="Album name"
          required
          title="Album name"
        />
      </FormRow>
      <FormRow :cols="2">
        <FormRow>
          <template #label>Artist</template>
          <TextInput
            v-model="album.artist_name"
            name="artist"
            disabled
            title="Artist name cannot be changed"
          />
        </FormRow>
        <FormRow>
          <template #label>Release year</template>
          <TextInput
            v-model="year"
            type="number"
            name="year"
            title="Release year"
            min="1000"
          />
        </FormRow>
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useOverlay } from '@/composables/useOverlay'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useDialogBox } from '@/composables/useDialogBox'
import { useModal } from '@/composables/useModal'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { albumStore } from '@/stores/albumStore'

import FormRow from '@/components/ui/form/FormRow.vue'
import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const album = useModal().getFromContext<Album>('album')
const name = ref(album.name)
const year = ref(album.year)

const close = () => emit('close')

const submit = async () => {
  showOverlay()

  try {
    await albumStore.update(album, {
      name: name.value,
      year: year.value,
    })

    toastSuccess('Album updated.')
    close()
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}

const isPristine = () => album.name === name.value && album.year === year.value

const maybeClose = async () => {
  if (isPristine()) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>

<style scoped lang="postcss">

</style>
