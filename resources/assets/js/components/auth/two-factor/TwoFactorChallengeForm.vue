<template>
  <AuthFormCard :failed data-testid="two-factor-challenge-form" @submit="handleSubmit">
    <TwoFactorChallengeInput ref="codeInput" v-model="data.code" @complete="handleSubmit">
      <template #totp-label>
        <p class="text-[.95rem] text-k-fg-70 mb-4">Enter the verification code from your authenticator app.</p>
      </template>
      <template #recovery-label>
        <p class="text-[.95rem] text-k-fg-70 mb-4">Enter one of your recovery codes.</p>
      </template>
    </TwoFactorChallengeInput>

    <Btn class="w-full" data-testid="submit" type="submit">Verify</Btn>

    <Btn
      bordered
      class="w-full !mt-6"
      data-testid="cancel"
      type="button"
      variant="ghost"
      @click.prevent="$emit('cancel')"
    >
      Back to login
    </Btn>
  </AuthFormCard>
</template>

<script lang="ts" setup>
import { onBeforeUnmount, ref, useTemplateRef } from 'vue'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import AuthFormCard from '@/components/auth/AuthFormCard.vue'
import TwoFactorChallengeInput from '@/components/auth/two-factor/TwoFactorChallengeInput.vue'

const props = defineProps<{ loginToken: string }>()
const emit = defineEmits<{ (e: 'verified'): void; (e: 'cancel'): void }>()

const failed = ref(false)
const codeInput = useTemplateRef<InstanceType<typeof TwoFactorChallengeInput>>('codeInput')

let errorResetTimer: number | null = null

const clearErrorResetTimer = () => {
  if (errorResetTimer !== null) {
    window.clearTimeout(errorResetTimer)
    errorResetTimer = null
  }
}

const { data, handleSubmit } = useForm<{ code: string }>({
  initialValues: { code: '' },
  onSubmit: async ({ code }) => await authService.submitTwoFactorChallenge(props.loginToken, code),
  onSuccess: () => {
    failed.value = false
    emit('verified')
  },
  onError: (error: unknown) => {
    failed.value = true
    logger.error(error)
    codeInput.value?.reset()
    clearErrorResetTimer()
    errorResetTimer = window.setTimeout(() => {
      failed.value = false
      errorResetTimer = null
    }, 2000)
  },
})

onBeforeUnmount(clearErrorResetTimer)
</script>
