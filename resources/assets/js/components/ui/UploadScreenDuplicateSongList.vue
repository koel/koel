<template>
  <div class="duplicate-song-list">
    <h3 class="text-secondary uppercase tracking-wider text-xs font-bold mb-2">
      Duplicated songs
    </h3>
    <ul>
      <li v-for="song in songs" :key="song.id" class="py-1 text-sm border-b border-white/5">
        {{ song.filename }}
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { DuplicateUpload } from '@/services/uploadService' // Import the interface

defineProps<{
  songs: DuplicateUpload[]
}>()

</script>
