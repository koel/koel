<template>
  <FormRow>
    <template #label>Theme</template>
    <SelectBox v-model="model">
      <option v-for="theme in themes" :key="theme.id" :value="theme.id">{{ theme.name }}</option>
    </SelectBox>
  </FormRow>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { themeStore } from '@/stores/themeStore'

import SelectBox from '@/components/ui/form/SelectBox.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const model = defineModel<Theme>()

const themes = ref<Theme[]>([])

onMounted(async () => {
  await themeStore.fetchCustomThemes()
  themes.value = themeStore.all
})
</script>
