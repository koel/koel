<template>
  <fieldset>
    <h4>
      <slot name="label" />
    </h4>

    <span class="w-48 h-48 my-4 aspect-square relative block rounded-md">
      <img :src="model" alt="" class="rounded object-cover">
      <button
        v-if="hasCustomValue"
        class="absolute top-2 right-2 w-9 active:scale-95 bg-black/50 hover:bg-black/70 aspect-square border border-white/10 rounded"
        type="button"
        @click.prevent="removeCustomValue"
      >
        <Icon :icon="faTrashCan" />
        <span class="sr-only">Remove</span>
      </button>
    </span>

    <FormRow v-if="!hasCustomValue">
      <FileInput accept="image/*" :name @change="onImageInputChange">Select an image</FileInput>
      <template #help>Recommended size: 512×512 pixels.</template>
    </FormRow>

    <p class="text-k-text-secondary text-[.95rem]">
      <slot name="help" />
    </p>
  </fieldset>
</template>

<script setup lang="ts">
import { faTrashCan } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted } from 'vue'
import { useImageFileInput } from '@/composables/useImageFileInput'

import FileInput from '@/components/ui/form/FileInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const props = defineProps<{ default: string, name: string }>()

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
