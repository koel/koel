<template>
  <form class="license-form flex items-stretch" @submit.prevent="validateLicenseKey">
    <TextInput
      v-model="licenseKey"
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
<script setup lang="ts">
import { ref } from 'vue'
import { plusService } from '@/services'
import { forceReloadWindow, logger } from '@/utils'
import { useDialogBox } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const { showSuccessDialog, showErrorDialog } = useDialogBox()
const licenseKey = ref('')
const loading = ref(false)

const validateLicenseKey = async () => {
  try {
    loading.value = true
    await plusService.activateLicense(licenseKey.value)
    await showSuccessDialog('Thanks for purchasing Koel Plus! Koel will now refresh to activate the changes.')
    forceReloadWindow()
  } catch (e) {
    logger.error(e)
    await showErrorDialog('Failed to activate Koel Plus. Please try again.')
  } finally {
    loading.value = false
  }
}
</script>
