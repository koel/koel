<template>
  <article class="space-y-3" @paste="onPaste">
    <span v-if="model" class="block size-24 aspect-square relative">
      <img :src="model" alt="" class="w-24 h-24 rounded object-cover" />
      <button
        class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
        type="button"
        @click.prevent="removeOrRevert"
      >
        {{ hasCustomArtwork ? 'Revert' : 'Remove' }}
      </button>
    </span>
    <FileInput accept="image/*" name="cover" @change="onImageInputChange">
      <slot>Select or paste a file…</slot>
    </FileInput>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useFileReader } from '@/composables/useFileReader'
import { useImageFileInput } from '@/composables/useImageFileInput'

import FileInput from '@/components/ui/form/FileInput.vue'

const model = defineModel<string | null | undefined>()
const defaultValue = model.value

const hasCustomArtwork = computed(() => defaultValue && model.value !== defaultValue)

const removeOrRevert = () => (model.value = hasCustomArtwork.value ? defaultValue : '')

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (model.value = dataUrl),
})

const onPaste = (event: ClipboardEvent) => {
  const clipboardData = event.clipboardData

  if (!clipboardData) {
    return
  }

  const file =
    Array.from(clipboardData.files || []).find((f: File) => f.type.startsWith('image/')) ||
    Array.from(clipboardData.items || [])
      .filter((item: DataTransferItem) => item.kind === 'file')
      .map((item: DataTransferItem) => item.getAsFile())
      .find((f: File | null): f is File => f !== null && f.type.startsWith('image/'))

  if (!file) {
    return
  }

  event.preventDefault()

  // Create a fresh FileReader per paste to avoid accumulating listeners on a shared instance.
  const { readAsDataUrl } = useFileReader()
  readAsDataUrl(file, dataUrl => (model.value = dataUrl))
}
</script>
