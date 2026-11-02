<template>
  <div class="flex items-center gap-2 cursor-pointer">
    <CheckBox
      v-model="preferences.transcode_on_mobile"
      class="shrink-0"
      data-testid="transcode_on_mobile"
      name="transcode_on_mobile"
    />
    <span>
      Convert and play media at
      <select
        v-model="preferences.transcode_quality"
        :disabled="!preferences.transcode_on_mobile"
        class="appearance-auto rounded-sm"
      >
        <option v-for="quality in [64, 96, 128, 192, 256, 320]" :key="quality" :value="quality">
          {{ quality }}
        </option>
      </select>
      kbps on mobile
    </span>
  </div>
</template>

<script lang="ts" setup>
import { preferenceStore as preferences } from '@/stores/preferenceStore'

import CheckBox from '@/components/ui/form/CheckBox.vue'
</script>
