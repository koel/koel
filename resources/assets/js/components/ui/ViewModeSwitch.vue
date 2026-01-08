<template>
  <span
    class="flex w-[64px] border border-solid border-k-fg-20 rounded-md overflow-hidden"
  >
    <label
      v-koel-tooltip
      :class="{ active: value === 'thumbnails' }"
      class="thumbnails"
      data-testid="view-mode-thumbnails"
      :title="t('ui.tooltips.viewThumbnails')"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="thumbnails">
      <LayoutGridIcon :size="16" />
      <span class="hidden">{{ t('ui.tooltips.viewThumbnails') }}</span>
    </label>

    <label
      v-koel-tooltip
      :class="{ active: value === 'list' }"
      class="list"
      data-testid="view-mode-list"
      :title="t('ui.tooltips.viewList')"
    >
      <input v-model="value" class="hidden" name="view-mode" type="radio" value="list">
      <LayoutListIcon :size="16" />
      <span class="hidden">{{ t('ui.tooltips.viewList') }}</span>
    </label>
  </span>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { LayoutGridIcon, LayoutListIcon } from 'lucide-vue-next'

const { t } = useI18n()

const value = defineModel<ViewMode>({ default: 'thumbnails' })
</script>

<style lang="postcss" scoped>
label {
  @apply w-1/2 flex items-center justify-center h-full mb-0 cursor-pointer;

  &.active {
    @apply bg-k-fg text-k-bg;
  }
}
</style>
