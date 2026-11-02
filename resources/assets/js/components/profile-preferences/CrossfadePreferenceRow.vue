<template>
  <div class="flex items-center gap-2 cursor-pointer">
    <CheckBox
      :model-value="crossfadeEnabled"
      class="shrink-0"
      name="crossfade"
      data-testid="crossfade-toggle"
      @update:model-value="toggleCrossfade"
    />
    <span class="flex-1">
      <span class="flex items-center gap-3">
        <label id="crossfade-label" for="crossfade-slider" class="shrink-0">Crossfade songs</label>
        <input
          id="crossfade-slider"
          v-model.number="preferences.crossfade_duration"
          type="range"
          min="0"
          max="15"
          step="1"
          data-testid="crossfade-slider"
          class="crossfade-slider flex-1 min-w-32 max-w-96"
        />
        <span class="text-k-fg-50 shrink-0">
          {{ crossfadeEnabled ? `${preferences.crossfade_duration}s` : 'Off' }}
        </span>
      </span>
    </span>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'

import CheckBox from '@/components/ui/form/CheckBox.vue'

const crossfadeEnabled = computed(() => preferences.crossfade_duration > 0)

const toggleCrossfade = (enabled: boolean | undefined) => {
  preferences.crossfade_duration = enabled ? 7 : 0
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.crossfade-slider {
  appearance: none;
  height: 4px;
  border-radius: 2px;
  outline: none;
  @apply bg-k-fg-10;
  cursor: pointer;
}

.crossfade-slider::-webkit-slider-thumb {
  appearance: none;
  height: 14px;
  width: 14px;
  border-radius: 50%;
  border: 0;
  cursor: pointer;
  @apply bg-k-fg;
}

.crossfade-slider::-moz-range-thumb {
  height: 14px;
  width: 14px;
  border-radius: 50%;
  border: 0;
  cursor: pointer;
  @apply bg-k-fg;
}
</style>
