<template>
  <div class="space-y-6">
    <section v-if="!supported" class="text-k-fg-70">Offline playback is not supported in this browser.</section>

    <template v-else>
      <section class="space-y-3">
        <h4 class="font-semibold text-k-fg uppercase tracking-wider text-sm">Storage Usage</h4>
        <div class="space-y-2">
          <div class="flex items-center gap-4">
            <div class="flex-1 h-2 bg-k-fg-10 rounded-full overflow-hidden">
              <div class="h-full bg-k-highlight rounded-full transition-all" :style="{ width: usagePercent + '%' }" />
            </div>
            <span class="text-sm text-k-fg-70 whitespace-nowrap">{{ usageLabel }}</span>
          </div>
          <p class="text-sm text-k-fg-70">
            {{ cachedSongCount }} {{ cachedSongCount === 1 ? 'song' : 'songs' }} available offline
          </p>
        </div>
      </section>

      <section v-if="cachedSongCount" class="space-y-3">
        <Btn danger small @click.prevent="clearAll">Clear All</Btn>
      </section>
    </template>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { formatBytes } from '@/utils/formatters'

import Btn from '@/components/ui/form/Btn.vue'

const { swReady, storageUsage, storageQuota, cachedSongCount, clearAllOfflineCache } = useOfflinePlayback()

const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()

const supported = computed(() => swReady.value)

const usagePercent = computed(() => {
  if (!storageQuota.value) return 0
  return Math.min((storageUsage.value / storageQuota.value) * 100, 100)
})

const usageLabel = computed(() => {
  if (!storageQuota.value) return formatBytes(storageUsage.value)
  return `${formatBytes(storageUsage.value)} / ${formatBytes(storageQuota.value)}`
})

const clearAll = async () => {
  if (await showConfirmDialog('Remove all offline songs? This cannot be undone.')) {
    await clearAllOfflineCache()
    toastSuccess('All offline songs have been removed.')
  }
}
</script>
