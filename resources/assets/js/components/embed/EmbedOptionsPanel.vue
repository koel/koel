<template>
  <div class="options grid gap-4" :class="isPlus && 'grid-cols-2'">
    <FormRow>
      <template #label>{{ t('embeds.layout') }}</template>
      <SelectBox v-model="options.layout">
        <option v-for="layout in layouts" :key="layout.id" :value="layout.id">{{ layout.name }}</option>
      </SelectBox>
    </FormRow>

    <ThemeSelectBox v-if="isPlus" v-model="options.theme" />

    <FormRow v-if="isPlus" class="col-span-2">
      <template #label>
        <CheckBox v-model="options.preview" />
        <span class="text-base">{{ t('embeds.previewMode') }}</span>
      </template>
    </FormRow>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'

import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'

const props = defineProps<{ modelValue: EmbedOptions }>()

const ThemeSelectBox = defineAsyncComponent(() => import('@/components/embed/ThemeSelectBox.vue'))

const { modelValue: options } = toRefs(props)

const { t } = useI18n()
const { isPlus } = useKoelPlus()

const layouts = computed<EmbedLayout[]>(() => [
  { id: 'full', name: t('embeds.bannerAndTracklist') },
  { id: 'compact', name: t('embeds.bannerOnly') },
])
</script>
