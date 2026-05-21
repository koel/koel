<template>
  <form :class="mode" @submit.prevent="submit">
    <div class="relative">
      <textarea
        ref="textareaEl"
        v-model="text"
        v-koel-focus
        :readonly="disabled"
        class="block w-full bg-k-bg-input text-k-fg-input border border-k-fg-10 resize-none focus:outline-hidden focus:border-k-highlight"
        :placeholder="
          mode === 'initial'
            ? 'Ask Koel to play songs, create playlists, add radio stations, and more.'
            : 'Send a message…'
        "
        :rows="mode === 'chat' ? 1 : undefined"
        name="prompt"
        @keydown.enter.exact.prevent="submit"
        @input="mode === 'chat' && autoResize()"
      />
      <button
        :disabled="!text.trim() || disabled"
        :style="mode === 'chat' ? { height: `${buttonSize}px`, width: `${buttonSize}px` } : undefined"
        class="absolute flex items-center justify-center rounded-full bg-k-highlight text-white disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-opacity"
        title="Send"
        type="submit"
      >
        <ArrowUpIcon :class="mode === 'initial' ? 'w-5 h-5' : 'w-4 h-4'" />
      </button>
    </div>
  </form>
</template>

<script lang="ts" setup>
import { ArrowUpIcon } from 'lucide-vue-next'
import { onMounted, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    mode?: 'initial' | 'chat'
    disabled?: boolean
  }>(),
  {
    mode: 'initial',
    disabled: false,
  },
)

const emit = defineEmits<{ (e: 'submit', text: string): void }>()

const textareaEl = ref<HTMLTextAreaElement>()
const text = ref('')
const buttonSize = ref(0)
const initialHeight = ref(0)

onMounted(() => {
  if (props.mode === 'chat' && textareaEl.value) {
    initialHeight.value = textareaEl.value.offsetHeight
    buttonSize.value = initialHeight.value - 14
  }
})

const autoResize = () => {
  const el = textareaEl.value

  if (el) {
    el.style.height = 'auto'
    el.style.height = `${Math.max(Math.min(el.scrollHeight, 120), initialHeight.value)}px`
  }
}

const submit = () => {
  if (!text.value.trim() || props.disabled) {
    return
  }

  emit('submit', text.value.trim())
  text.value = ''

  if (textareaEl.value) {
    textareaEl.value.style.height = initialHeight.value ? `${initialHeight.value}px` : 'auto'
    textareaEl.value.focus()
  }
}

const focus = () => textareaEl.value?.focus()

const fill = (value: string) => {
  text.value = value
  focus()
}

defineExpose({ fill, focus })
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.initial textarea {
  @apply h-40 p-6 pr-14 pb-12 text-xl rounded-3xl;
}

.initial button {
  @apply right-3 bottom-4 w-9 h-9;
}

.chat textarea {
  @apply py-4 px-6 pr-16 text-lg rounded-[25px];
}

.chat button {
  @apply right-2 bottom-2;
}
</style>
