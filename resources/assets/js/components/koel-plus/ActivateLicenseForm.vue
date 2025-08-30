<template>
  <form class="license-form flex items-stretch" @submit.prevent="handleSubmit">
    <TextInput
      v-model="data.licenseKey"
      v-koel-focus
      :disabled="loading"
      class="!rounded-r-none"
      name="license"
      placeholder="Enter your license key"
      required
    />
    <Btn :disabled="loading" class="!rounded-l-none" type="submit">Activate</Btn>
  </form>
</template>

<script lang="ts" setup>
import { plusService } from '@/services/plusService'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { forceReloadWindow } from '@/utils/helpers'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const { showSuccessDialog } = useDialogBox()

const { data, loading, handleSubmit } = useForm<{ licenseKey: string }>({
  initialValues: {
    licenseKey: '',
  },
  onSubmit: async ({ licenseKey }) => await plusService.activateLicense(licenseKey),
  onSuccess: async () => {
    await showSuccessDialog('Thanks for purchasing Koel Plus! Koel will now refresh to activate the changes.')
    forceReloadWindow()
  },
  onError: (error: unknown) => useErrorHandler('dialog').handleHttpError(error),
})
</script>
