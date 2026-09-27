<template>
  <article
    v-if="state.status !== 'idle'"
    :data-status="state.status"
    class="fixed right-6 z-10000 flex w-80 flex-col gap-3 rounded-md bg-white p-4 text-gray-800 shadow-lg"
    data-testid="zip-download-panel"
  >
    <header class="flex items-center gap-3">
      <FileArchiveIcon :size="20" class="shrink-0 text-k-primary" />
      <span class="min-w-0 flex-1 truncate">{{ state.archiveName }}</span>
      <button
        v-if="state.status !== 'zipping'"
        class="shrink-0 text-gray-500 hover:text-gray-800"
        title="Dismiss"
        type="button"
        @click="zipDownloadService.dismiss()"
      >
        <XIcon :size="16" />
      </button>
    </header>

    <template v-if="state.status === 'zipping'">
      <progress
        :max="state.bytesTotal || 1"
        :value="state.bytesDone"
        class="h-1.5 w-full appearance-none overflow-hidden rounded-full [&::-moz-progress-bar]:bg-k-primary [&::-webkit-progress-bar]:bg-gray-200 [&::-webkit-progress-value]:bg-k-primary"
      />
      <footer class="flex items-center justify-between gap-3">
        <span class="text-sm text-gray-500 tabular-nums">Preparing… {{ formatBytes(state.bytesDone) }}</span>
        <Btn size="small" variant="ghost" bordered @click="zipDownloadService.cancel()">Cancel</Btn>
      </footer>
    </template>

    <footer v-else-if="state.status === 'ready'" class="flex items-center justify-between gap-3">
      <span class="text-sm text-gray-500">Ready to save.</span>
      <Btn size="small" @click="zipDownloadService.save()">Save</Btn>
    </footer>

    <p v-else class="text-sm text-k-danger">{{ state.error }}</p>
  </article>
</template>

<script lang="ts" setup>
import { FileArchiveIcon, XIcon } from 'lucide-vue-next'
import { zipDownloadService } from '@/services/zipDownloadService'
import { formatBytes } from '@/utils/formatters'

import Btn from '@/components/ui/form/Btn.vue'

const { state } = zipDownloadService
</script>

<style lang="postcss" scoped>
article {
  bottom: calc(var(--footer-height) + 1.2rem);
}
</style>
