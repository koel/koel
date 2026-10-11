<template>
  <TextInput v-model="value" :type name="value[]" required />
</template>

<script lang="ts" setup>
import type inputTypes from '@/config/smart-playlist/inputTypes'

import TextInput from '@/components/ui/form/TextInput.vue'

defineProps<{ type: keyof typeof inputTypes }>()
const value = defineModel<any>()
</script>
