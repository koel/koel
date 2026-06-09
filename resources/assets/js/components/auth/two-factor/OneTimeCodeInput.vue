<template>
  <div class="flex justify-between max-w-[256px]" data-testid="one-time-code-input">
    <input
      v-for="(_, i) in 6"
      :key="i"
      ref="boxes"
      v-model="digits[i]"
      :autofocus="i === 0"
      autocomplete="one-time-code"
      class="w-[36px] h-12 text-center text-lg font-mono bg-k-bg-input text-k-fg-input border border-k-fg-10 rounded-sm focus-visible:outline-none focus-visible:border-k-highlight"
      inputmode="numeric"
      maxlength="1"
      required
      @input="onInput(i, $event)"
      @keydown="onKeydown(i, $event)"
      @paste="onPaste"
    />
  </div>
</template>

<script lang="ts" setup>
import { ref, useTemplateRef, watch } from 'vue'

const model = defineModel<string>({ default: '' })
const emit = defineEmits<{ (e: 'complete', code: string): void }>()

const digits = ref<string[]>(['', '', '', '', '', ''])
const boxes = useTemplateRef<HTMLInputElement[]>('boxes')

watch(
  model,
  value => {
    const cleaned = (value || '').replace(/\D/g, '').slice(0, 6)

    for (let i = 0; i < 6; i++) {
      digits.value[i] = cleaned[i] ?? ''
    }
  },
  { immediate: true },
)

const sync = () => {
  const joined = digits.value.join('')
  model.value = joined

  if (joined.length === 6) {
    emit('complete', joined)
  }
}

const onInput = (i: number, event: Event) => {
  const target = event.target as HTMLInputElement
  const value = target.value.replace(/\D/g, '').slice(0, 1)
  digits.value[i] = value

  if (value && i < 5) {
    boxes.value?.[i + 1]?.focus()
  }

  sync()
}

const onKeydown = (i: number, event: KeyboardEvent) => {
  if (event.key === 'Backspace' && !digits.value[i] && i > 0) {
    boxes.value?.[i - 1]?.focus()
  } else if (event.key === 'ArrowLeft' && i > 0) {
    boxes.value?.[i - 1]?.focus()
  } else if (event.key === 'ArrowRight' && i < 5) {
    boxes.value?.[i + 1]?.focus()
  }
}

const onPaste = (event: ClipboardEvent) => {
  event.preventDefault()
  const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6)

  for (let i = 0; i < 6; i++) {
    digits.value[i] = pasted[i] ?? ''
  }

  const focusIndex = Math.min(pasted.length, 5)
  boxes.value?.[focusIndex]?.focus()

  sync()
}

const focus = () => boxes.value?.[0]?.focus()

defineExpose({ focus })
</script>
