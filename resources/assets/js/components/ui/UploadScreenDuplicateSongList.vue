<template>
  <div class="rounded-lg border border-k-warning/20 overflow-hidden">
    <button
      class="flex items-center justify-between w-full px-4 py-3 bg-k-warning/10 text-k-warning hover:bg-k-warning/15 transition-colors"
      type="button"
      @click="isExpanded = !isExpanded"
    >
      <span class="flex items-center gap-3">
        <Icon :icon="faExclamationTriangle" />
        <strong>Duplicate files detected</strong>
        <span class="text-xs bg-k-warning/20 px-2 py-0.5 rounded-full uppercase font-bold">
          {{ songs.length }}
        </span>
      </span>
      <Icon :icon="isExpanded ? faChevronUp : faChevronDown" />
    </button>

    <div v-if="isExpanded" class="border-t border-k-warning/20">
      <div
        v-for="song in songs"
        :key="song.id"
        class="flex items-center gap-3 px-4 py-2.5 border-b border-k-fg-5 last:border-b-0"
      >
        <span class="flex-1 min-w-0">
          <span class="block truncate">{{ song.existing_song.artist_name }} — {{ song.existing_song.title }}</span>
          <span class="block text-k-fg-50 text-[0.85rem]">
            Added {{ new Date(song.existing_song.created_at).toLocaleDateString() }}
          </span>
        </span>
        <Btn small transparent @click="keepDuplicates([song.id])">Upload anyway</Btn>
        <Btn small transparent class="!text-k-danger" @click="deleteDuplicates([song.id])">Discard</Btn>
      </div>

      <div class="flex justify-end gap-2 px-4 py-3 bg-k-fg-5">
        <Btn small transparent class="!text-k-danger" @click="deleteDuplicates(songs.map(({ id }) => id))">
          Discard all
        </Btn>
        <Btn small highlight @click="keepDuplicates(songs.map(({ id }) => id))">Upload all anyway</Btn>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { faChevronDown, faChevronUp, faExclamationTriangle } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { useDuplicateUploads } from '@/composables/useDuplicateUploads'

import Btn from '@/components/ui/form/Btn.vue'

import type { DuplicateUpload } from '@/services/uploadService'

defineProps<{ songs: DuplicateUpload[] }>()

const isExpanded = ref(false)
const { keepDuplicates, deleteDuplicates } = useDuplicateUploads()
</script>
