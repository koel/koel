<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>New Podcast</h1>
    </header>

    <main>
      <FormRow>
        <template #label>Podcast feed URL</template>
        <TextInput
          type="url"
          v-model="url"
          v-koel-focus
          name="url"
          placeholder="https://example.com/feed.xml"
          required
        />
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
import { useDialogBox, useErrorHandler, useMessageToaster, useOverlay } from '@/composables'
import { podcastStore } from '@/stores'

import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const url = ref('')

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const submit = async () => {
  showOverlay()

  try {
    const podcast = await podcastStore.store(url.value)
    close()
    toastSuccess(`Podcast "${podcast.title}" added.`)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error, {
      409: 'You have already subscribed to this podcast.'
    })
  } finally {
    hideOverlay()
  }
}

const maybeClose = async () => {
  if (url.value.trim() === '') {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>
