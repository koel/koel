<template>
  <div class="space-y-4">
    <p>Scan this QR code with your authenticator app (1Password, Authy, Google Authenticator, etc.).</p>

    <div class="block w-fit rounded-md overflow-hidden bg-white p-2">
      <img :src="qrCodeUrl" alt="Two-factor authentication QR code" height="192" width="192" />
    </div>

    <form class="space-y-3 max-w-md" data-testid="two-factor-enrollment-form" @submit.prevent="handleSubmit">
      <FormRow>
        <template #label>Enter the 6-digit code shown in your authenticator app.</template>
        <TextInput v-model="data.code" v-koel-focus autocomplete="one-time-code" placeholder="123 456" required />
      </FormRow>
      <div class="flex gap-2">
        <Btn :disabled="submitting" type="submit">Confirm</Btn>
        <Btn type="button" variant="ghost" @click.prevent="$emit('cancel')">Cancel</Btn>
      </div>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { useQRCode } from '@vueuse/integrations/useQRCode'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const props = defineProps<{ provisioningUri: string; submitting?: boolean }>()
const emit = defineEmits<{ (e: 'cancel'): void; (e: 'submit', code: string): void }>()

const qrCodeUrl = useQRCode(toRef(props, 'provisioningUri'), {
  width: window.devicePixelRatio === 1 ? 192 : 384,
  height: window.devicePixelRatio === 1 ? 192 : 384,
})

const { data, handleSubmit } = useForm<{ code: string }>({
  initialValues: { code: '' },
  useOverlay: false,
  onSubmit: form => emit('submit', form.code),
})
</script>
