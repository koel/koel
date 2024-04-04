<template>
  <form class="license-form" @submit.prevent="validateLicenseKey">
    <input
      v-model="licenseKey"
      v-koel-focus
      type="text"
      name="license"
      placeholder="Enter your license key"
      required
      :disabled="loading"
    >
    <Btn blue type="submit" :disabled="loading">Activate</Btn>
  </form>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { plusService } from '@/services'
import { forceReloadWindow, logger } from '@/utils'
import { useDialogBox } from '@/composables'

import Btn from '@/components/ui/Btn.vue'

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


<style scoped lang="postcss">
form {
  display: flex;
  align-items: stretch;

  &:has(:focus) {
    outline: 4px solid rgba(255, 255, 255, 0);
  }

  input {
    border-radius: 4px 0 0 4px;
  }

  button {
    border-radius: 0 4px 4px 0;
  }
}
</style>
