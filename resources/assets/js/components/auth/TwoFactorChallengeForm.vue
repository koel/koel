<template>
  <form
    :class="{ error: failed }"
    class="sm:border duration-500 p-7 rounded-xl border-transparent sm:bg-k-fg-10 space-y-3"
    data-testid="two-factor-challenge-form"
    @submit.prevent="handleSubmit"
  >
    <div class="text-center mb-8">
      <img alt="Logo" class="inline-block" :src="logo" width="156" />
    </div>

    <p class="text-center text-[.95rem] text-k-fg-70 mb-4">
      Enter the code from your authenticator app or a recovery code to continue.
    </p>

    <FormRow>
      <TextInput
        v-model="data.code"
        autocomplete="one-time-code"
        autofocus
        placeholder="Authentication code"
        required
      />
    </FormRow>

    <FormRow>
      <Btn class="w-full" data-testid="submit" type="submit">Verify</Btn>
    </FormRow>

    <FormRow>
      <Btn class="w-full" type="button" variant="ghost" @click.prevent="$emit('cancel')">Back to login</Btn>
    </FormRow>
  </form>
</template>

<script lang="ts" setup>
import { onBeforeUnmount, ref } from 'vue'
import { authService } from '@/services/authService'
import { logger } from '@/utils/logger'
import { useBranding } from '@/composables/useBranding'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const props = defineProps<{ loginToken: string }>()
const emit = defineEmits<{ (e: 'verified'): void; (e: 'cancel'): void }>()

const { logo } = useBranding()

const failed = ref(false)
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
    clearErrorResetTimer()
    errorResetTimer = window.setTimeout(() => {
      failed.value = false
      errorResetTimer = null
    }, 2000)
  },
})

onBeforeUnmount(clearErrorResetTimer)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

@keyframes shake {
  8%,
  41% {
    transform: translateX(-10px);
  }
  25%,
  58% {
    transform: translateX(10px);
  }
  75% {
    transform: translateX(-5px);
  }
  92% {
    transform: translateX(5px);
  }
  0%,
  100% {
    transform: translateX(0);
  }
}

form.error {
  @apply border-red-500;
  animation: shake 0.5s;
}
</style>
