<template>
  <fieldset class="flex items-start gap-4 py-3">
    <img :src="model" alt="" class="size-[104px] shrink-0 rounded-md bg-k-fg-5 object-contain p-1" />

    <div class="min-w-0 flex-1">
      <h4 class="text-k-fg">
        <slot name="label" />
      </h4>
      <p class="text-[.95rem] text-k-fg-50">
        <slot name="help" />
      </p>
      <p class="text-[.95rem] text-k-fg-50">Recommended size: 512×512 pixels or larger.</p>

      <div class="mt-3 flex items-center gap-4">
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
          Reset
        </button>
      </div>
    </div>
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
