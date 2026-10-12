<template>
  <div
    v-if="screenHeader"
    :style="{ height: `${screenHeader.expandedHeight.value}px` }"
    aria-hidden="true"
    class="shrink-0"
  />
</template>

<script lang="ts" setup>
import { inject } from 'vue'
import { ScrollAwayHeaderKey } from '@/config/symbols'

const screenHeader = inject(ScrollAwayHeaderKey, null)
</script>
