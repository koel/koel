<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>New Podcast</h1>
    </header>

    <main>
      <FormRow>
        <template #label>Podcast feed URL</template>
        <TextInput
          v-model="data.url"
          v-koel-focus
          :disabled="loading"
          name="url"
          placeholder="https://example.com/feed.xml"
          required
          type="url"
        />
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn :disabled="loading" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { podcastStore } from '@/stores/podcastStore'
import { useForm } from '@/composables/useForm'

import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { loading, handleSubmit, data, isPristine } = useForm<Pick<Podcast, 'url'>>({
  initialValues: {
    url: '',
  },
  onSubmit: async ({ url }) => await podcastStore.store(url),
  onSuccess: (podcast: Podcast) => {
    close()
    toastSuccess(`Podcast "${podcast.title}" added.`)
  },
  onError: error => useErrorHandler('dialog').handleHttpError(error, {
    409: 'You have already subscribed to this podcast.',
  }),
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
