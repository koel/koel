<template>
  <div class="options grid gap-4" :class="isPlus && 'grid-cols-2'">
    <FormRow>
      <template #label>Layout</template>
      <SelectBox v-model="options.layout">
        <option v-for="layout in layouts" :key="layout.id" :value="layout.id">{{ layout.name }}</option>
      </SelectBox>
    </FormRow>

    <FormRow v-if="isPlus">
      <template #label>Theme</template>
      <SelectBox v-model="options.theme">
        <option v-for="theme in themeStore.all" :key="theme.id" :value="theme.id">{{ theme.name }}</option>
      </SelectBox>
    </FormRow>

    <FormRow v-if="isPlus" class="col-span-2">
      <template #label>
        <CheckBox v-model="options.preview" />
        <span class="text-base">Preview mode – only play max. 30 seconds per track</span>
      </template>
    </FormRow>
  </div>
</template>

<script setup lang="ts">
import { useKoelPlus } from '@/composables/useKoelPlus'
import { toRefs } from 'vue'
import { themeStore } from '@/stores/themeStore'

import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'

const props = defineProps<{ modelValue: EmbedOptions }>()
const { modelValue: options } = toRefs(props)

const { isPlus } = useKoelPlus()

const layouts: EmbedLayout[] = [
  { id: 'full', name: 'Banner and tracklist' },
  { id: 'compact', name: 'Banner only' },
]
</script>

<style scoped lang="postcss">

</style>
