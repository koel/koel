<template>
  <div class="space-y-3">
    <template v-if="mode === 'totp'">
      <slot name="totp-label" />

      <OneTimeCodeInput ref="totpInput" v-model="totpModel" @complete="$emit('complete', $event)" />

      <button
        class="text-[.95rem] text-k-fg-70 cursor-pointer hover:text-k-highlight focus-visible:text-k-highlight self-start"
        data-testid="use-recovery-code"
        type="button"
        @click.prevent="switchMode('recovery')"
      >
        Use a recovery code
      </button>
    </template>

    <template v-else>
      <slot name="recovery-label" />

      <TextInput
        v-model="recoveryModel"
        autocomplete="one-time-code"
        autofocus
        class="font-mono uppercase"
        data-testid="recovery-code-input"
        maxlength="39"
        required
        spellcheck="false"
      />

      <button
        class="text-[.95rem] text-k-fg-70 cursor-pointer hover:text-k-highlight focus-visible:text-k-highlight self-start"
        data-testid="use-totp-code"
        type="button"
        @click.prevent="switchMode('totp')"
      >
        Use authenticator code instead
      </button>
    </template>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref, useTemplateRef } from 'vue'

import OneTimeCodeInput from '@/components/auth/two-factor/OneTimeCodeInput.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

type Mode = 'totp' | 'recovery'

const model = defineModel<string>({ default: '' })
defineEmits<{ (e: 'complete', code: string): void }>()

const mode = ref<Mode>('totp')
const totpInput = useTemplateRef<InstanceType<typeof OneTimeCodeInput>>('totpInput')

const switchMode = (next: Mode) => {
  mode.value = next
  model.value = ''
}

const totpModel = computed<string>({
  get: () => model.value,
  set: value => {
    model.value = value
  },
})

const recoveryModel = computed<string>({
  get: () => model.value,
  set: value => {
    const raw = value
      .replace(/[^A-Za-z0-9]/g, '')
      .toUpperCase()
      .slice(0, 32)
    model.value = raw.match(/.{1,4}/g)?.join(' ') ?? ''
  },
})

const reset = () => {
  if (mode.value === 'totp') {
    model.value = ''
    totpInput.value?.focus()
  }
}

defineExpose({ reset, mode })
</script>
