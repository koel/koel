<template>
  <article class="space-y-3">
    <span v-if="model" class="block size-24 aspect-square relative">
      <img :src="model" alt="" class="w-24 h-24 rounded object-cover">
      <button
        class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
        type="button"
        @click.prevent="removeOrRevert"
      >
        {{ hasCustomArtwork ? 'Revert' : 'Remove' }}
      </button>
    </span>
    <FileInput accept="image/*" name="cover" @change="onImageInputChange">
      <slot>Select a file…</slot>
    </FileInput>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useImageFileInput } from '@/composables/useImageFileInput'

import FileInput from '@/components/ui/form/FileInput.vue'

const model = defineModel<string | null | undefined>()
const defaultValue = model.value

const hasCustomArtwork = computed(() => defaultValue && model.value !== defaultValue)

const removeOrRevert = () => (model.value = hasCustomArtwork.value ? defaultValue : '')

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (model.value = dataUrl),
})
</script>
