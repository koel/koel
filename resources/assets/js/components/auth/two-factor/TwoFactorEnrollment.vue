<template>
  <div class="space-y-4">
    <AlertBox type="warning"> Two-factor authentication may not work with older versions of Koel Player. </AlertBox>

    <p>1. Scan this QR code with your authenticator app – Authy, Google Authenticator, etc.</p>

    <div v-if="provisioningUri" class="block w-fit rounded-md overflow-hidden bg-white p-2">
      <img :src="qrCodeUrl" alt="Two-factor authentication QR code" height="192" width="192" />
    </div>

    <form class="space-y-3 max-w-md mt-6" data-testid="two-factor-enrollment-form" @submit.prevent="handleSubmit">
      <p class="text-[.95rem] text-k-fg-70">2. Enter the verification code from your authenticator app.</p>

      <FormRow>
        <OneTimeCodeInput v-model="data.code" @complete="handleSubmit" />
      </FormRow>

      <div class="flex gap-2 mt-6">
        <Btn :disabled="!provisioningUri" type="submit">Confirm</Btn>
        <Btn type="button" variant="ghost" @click.prevent="$emit('cancel')">Cancel</Btn>
      </div>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { useQRCode } from '@vueuse/integrations/useQRCode'
import { authService } from '@/services/authService'
import { useForm } from '@/composables/useForm'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import OneTimeCodeInput from '@/components/auth/two-factor/OneTimeCodeInput.vue'
import AlertBox from '@/components/ui/AlertBox.vue'

const emit = defineEmits<{ (e: 'cancel'): void; (e: 'enrolled', codes: string[]): void }>()

const { toastSuccess, toastError } = useMessageToaster()

const provisioningUri = ref('')

const qrCodeUrl = useQRCode(provisioningUri, {
  width: window.devicePixelRatio === 1 ? 192 : 384,
  height: window.devicePixelRatio === 1 ? 192 : 384,
})

onMounted(async () => {
  try {
    const { provisioning_uri } = await authService.enrollTwoFactor()
    provisioningUri.value = provisioning_uri
  } catch {
    toastError('Failed to start two-factor enrollment.')
    emit('cancel')
  }
})

const { data, handleSubmit } = useForm<{ code: string }>({
  initialValues: { code: '' },
  useOverlay: false,
  onSubmit: async ({ code }) => await authService.confirmTwoFactor(code),
  onSuccess: result => {
    toastSuccess('Two-factor authentication enabled.')
    emit('enrolled', result.recovery_codes)
  },
  onError: () => toastError('Invalid code.'),
})
</script>
