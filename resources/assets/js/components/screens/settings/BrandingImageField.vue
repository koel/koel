<template>
  <fieldset class="flex w-72 flex-col gap-3">
    <h4 class="text-k-fg">
      <slot name="label" />
    </h4>

    <div class="flex items-center gap-4">
      <img :src="model" alt="" class="size-32 shrink-0 rounded-lg border border-k-fg-10 bg-k-fg-5 object-contain p-2" />

      <div class="flex flex-col items-start gap-2">
        <label
          class="relative inline-flex cursor-pointer items-center rounded-md border border-k-fg-20 px-3 py-1.5 hover:bg-k-fg-10 has-focus-visible:outline-2 has-focus-visible:outline-k-highlight"
        >
          <input accept="image/*" :name class="sr-only" type="file" @change="onImageInputChange" />
          Change
        </label>
        <button
          v-if="hasCustomValue"
          class="text-k-fg-70 hover:text-k-fg"
          type="button"
          @click.prevent="removeCustomValue"
        >
          Reset to default
        </button>
      </div>
    </div>

    <p class="text-[.95rem] text-k-fg-50">
      <slot name="help" />
    </p>
  </fieldset>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useImageFileInput } from '@/composables/useImageFileInput'

const props = defineProps<{ default: string; name: string }>()

const model = defineModel<string>()
let initialValue: typeof model.value

const hasCustomValue = computed(() => model.value && model.value !== props.default)

const removeCustomValue = () => {
  // First reset the model to the initial value (current settings), then to the default fallback.
  model.value = model.value === initialValue ? props.default : initialValue
}

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (model.value = dataUrl),
})

onMounted(() => (initialValue = model.value))
</script>
