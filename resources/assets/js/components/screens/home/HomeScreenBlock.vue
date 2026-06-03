<script setup lang="ts">
import { onMounted, ref } from 'vue'

const stats = ref({
  songs: 0,
  albums: 0,
  artists: 0,
  duration: 0,
})

const loadingStats = ref(false)
const statsError = ref(false)

const fetchLibraryStats = async () => {
  loadingStats.value = true
  statsError.value = false

  try {
    const response = await fetch('/api/library/stats')
    stats.value = await response.json()
  } catch {
    statsError.value = true
  } finally {
    loadingStats.value = false
  }
}

onMounted(fetchLibraryStats)
</script>

<template>
  <div>
    <h3 class="text-2xl mb-8 font-thin text-k-fg">
      <slot name="header" />
    </h3>

    <section class="mb-6 rounded-lg border border-k-border bg-k-bg-secondary p-4">
      <h4 class="mb-2 text-base font-semibold text-k-fg">
        Library Statistics
      </h4>

      <p v-if="loadingStats" class="text-sm text-k-text-secondary">
        Loading library statistics...
      </p>

      <p v-else-if="statsError" class="text-sm text-k-text-secondary">
        Could not load library statistics.
      </p>

      <div v-else class="grid grid-cols-2 gap-2 text-sm text-k-text-secondary">
        <p>Songs: {{ stats.songs }}</p>
        <p>Albums: {{ stats.albums }}</p>
        <p>Artists: {{ stats.artists }}</p>
        <p>Duration: {{ stats.duration }}</p>
      </div>
    </section>

    <slot />
  </div>
</template>