<template>
  <label class="flex items-center gap-2 cursor-pointer">
    <CheckBox v-model="enabled" :name="name ?? preference" class="shrink-0" />
    <span>{{ label }}</span>
  </label>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'

import CheckBox from '@/components/ui/form/CheckBox.vue'

const props = defineProps<{
  preference: keyof UserPreferences
  label: string
  name?: string
}>()

const enabled = computed({
  get: () => preferences[props.preference],
  set: value => (preferences[props.preference] = value),
})
</script>
