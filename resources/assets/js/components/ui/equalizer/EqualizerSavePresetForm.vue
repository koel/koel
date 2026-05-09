<template>
  <form class="flex gap-2 items-center w-full" @submit.prevent="submit">
    <TextInput
      ref="nameInput"
      v-model="name"
      placeholder="Preset name"
      title="Preset name"
      @keydown.esc.prevent="$emit('cancel')"
    />
    <Btn type="submit" variant="ghost" :disabled="!name.trim()">Save</Btn>
    <Btn variant="ghost" @click.prevent="$emit('cancel')">Cancel</Btn>
  </form>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const emit = defineEmits<{
  (e: 'submit', name: string): void
  (e: 'cancel'): void
}>()

const name = ref('')
const nameInput = ref<InstanceType<typeof TextInput>>()

const submit = () => {
  const trimmed = name.value.trim()

  if (!trimmed) {
    return
  }

  emit('submit', trimmed)
}

onMounted(() => nameInput.value?.el?.focus())
</script>
