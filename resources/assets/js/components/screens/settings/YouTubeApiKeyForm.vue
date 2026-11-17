<template>
  <div v-if="hasApiKey">
    <p>An API key is saved.</p>
    <div class="mt-4">
      <Btn :disabled="removing" type="button" variant="ghost" bordered @click.prevent="remove">Remove</Btn>
    </div>
  </div>

  <form v-else class="flex items-start gap-2 lg:w-1/2" @submit.prevent="handleSubmit">
    <div class="flex-1">
      <PasswordField v-model="data.api_key" autocomplete="off" name="api_key" placeholder="YouTube API key" required />
    </div>
    <Btn :disabled="loading" type="submit">Save</Btn>
  </form>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useForm } from '@/composables/useForm'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { settingStore } from '@/stores/settingStore'

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'

const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler('dialog')

const hasApiKey = computed(() => Boolean(settingStore.state.youtube?.has_api_key))

const { data, loading, handleSubmit } = useForm<{ api_key: string }>({
  initialValues: { api_key: '' },
  toast: {
    loading: 'Saving…',
    success: 'YouTube API key saved.',
  },
  onSubmit: async ({ api_key }) => await settingStore.updateYouTubeApiKey(api_key),
  onSuccess: () => (data.api_key = ''),
})

const removing = ref(false)

const remove = async () => {
  removing.value = true

  try {
    await settingStore.updateYouTubeApiKey(null)
    toastSuccess('YouTube API key removed.')
  } catch (error: unknown) {
    handleHttpError(error)
  } finally {
    removing.value = false
  }
}
</script>
