<template>
  <form class="md:w-[420px] min-w-full" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('podcasts.new') }}</h1>
    </header>

    <main>
      <FormRow>
        <template #label>{{ t('podcasts.feedUrl') }}</template>
        <TextInput
          v-model="data.url"
          v-koel-focus
          :disabled="loading"
          name="url"
          :placeholder="t('podcasts.feedUrlPlaceholder')"
          required
          type="url"
        />
      </FormRow>
    </main>

    <footer>
      <Btn type="submit">{{ t('auth.save') }}</Btn>
      <Btn :disabled="loading" white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { podcastStore } from '@/stores/podcastStore'
import { useForm } from '@/composables/useForm'

import TextInput from '@/components/ui/form/TextInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { t } = useI18n()
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
    toastSuccess(t('podcasts.added', { title: podcast.title }))
  },
  onError: error => useErrorHandler('dialog').handleHttpError(error, {
    409: t('podcasts.alreadySubscribed'),
  }),
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('podcasts.discardChanges'))) {
    close()
  }
}
</script>
