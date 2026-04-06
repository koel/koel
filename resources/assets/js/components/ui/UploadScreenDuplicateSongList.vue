<template>
  <div class="dup-wrap" :class="{ 'is-collapsed': !isExpanded }">
    <div class="dup-header-banner" @click="isExpanded = !isExpanded">
      <div class="message-content">
        <FontAwesomeIcon :icon="faWarning" class="warning-icon" />
        <span><strong>Duplicate files</strong> detected</span>
        <span class="dup-count-badge">{{ songs.length }} songs</span>
      </div>

      <div class="controls">
        <button class="expand-btn">
          {{ isExpanded ? 'Hide songs' : 'View songs' }}
          <FontAwesomeIcon :icon="isExpanded ? faChevronUp : faChevronDown" class="ml-2" />
        </button>
      </div>
    </div>

    <div v-if="isExpanded" class="dup-content">
      <div v-for="song in songs" :key="song.id" class="dup-row">
        <div class="dup-icon">
          <FontAwesomeIcon :icon="faMusic" />
        </div>
        <div class="dup-text">
          <span class="dup-name">{{ song.existing_song.artist_name }} - {{ song.existing_song.title }} </span>
          <span class="dup-date">Added {{ new Date(song.existing_song.created_at).toLocaleDateString() }}</span>
        </div>
        <button class="dup-badge" @click="keepDuplicates([song.id])">
          <FontAwesomeIcon :icon="faCheck" />
          Upload anyway
        </button>
        <button class="btn-discard" @click="deleteDuplicates([song.id])">
          <FontAwesomeIcon :icon="faX" />
          Discard
        </button>
      </div>

      <div class="dup-footer">
        <button class="btn-discard-all" @click="deleteDuplicates(songs.map(({ id }) => id))">
          <FontAwesomeIcon :icon="faX" />
          Discard all
        </button>
        <button class="btn-upload-all" @click="keepDuplicates(songs.map(({ id }) => id))">
          <FontAwesomeIcon :icon="faUpload" />
          Upload all anyway
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  faCheck,
  faChevronDown,
  faChevronUp,
  faMusic,
  faUpload,
  faWarning,
  faX,
} from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { useDuplicateUploads } from '@/composables/useDuplicateUploads'
import type { DuplicateUpload } from '@/services/uploadService'

defineProps<{
  songs: DuplicateUpload[]
}>()

const isExpanded = ref(false)
const { keepDuplicates, deleteDuplicates } = useDuplicateUploads()
</script>

<style scoped>
.dup-wrap {
  background: #1a1a1a;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(245, 158, 11, 0.2);
  margin: 1rem auto;
  transition: all 0.3s ease;
  width: 100%;
  max-width: 800px;
}

.dup-header-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1.25rem;
  background-color: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  cursor: pointer;
  transition: background 0.2s;
}

.dup-header-banner:hover {
  background-color: rgba(245, 158, 11, 0.15);
}

.message-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.05rem;
}

.dup-count-badge {
  font-size: 0.8rem;
  background: rgba(245, 158, 11, 0.2);
  padding: 2px 8px;
  border-radius: 99px;
  margin-left: 4px;
  text-transform: uppercase;
  font-weight: bold;
}

.expand-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0.4rem 0.8rem;
  background: rgba(245, 158, 11, 0.2);
  border: none;
  border-radius: 6px;
  color: #f59e0b;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.85rem;
}

.dup-content {
  border-top: 1px solid rgba(245, 158, 11, 0.2);
  font-style: italic;
}

.dup-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.dup-icon {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  background: rgba(186, 117, 23, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #c99040;
}

.dup-text {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.dup-date {
  font-size: 0.9rem;
  color: #a0a0a0;
  margin-top: 2px;
}

.dup-name {
  flex: 1;
  font-size: 1.1rem;
  color: #e0e0e0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dup-badge,
.btn-discard {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  background: transparent;
  display: flex;
  align-items: center;
  gap: 5px;
}

.dup-badge {
  border: 1px solid #6dba82;
  color: #6dba82;
}
.dup-badge:hover {
  background: rgba(109, 186, 130, 0.1);
}

.btn-discard {
  border: 1px solid #e06060;
  color: #e06060;
}
.btn-discard:hover {
  background: rgba(224, 96, 96, 0.1);
}

.dup-footer {
  display: flex;
  padding: 12px 16px;
  background: rgba(0, 0, 0, 0.2);
  gap: 10px;
}

.btn-discard-all,
.btn-upload-all {
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-discard-all {
  border: 1px solid rgba(224, 96, 96, 0.4);
  color: #e06060;
  background: transparent;
}

.btn-upload-all {
  border: 1px solid rgba(109, 186, 130, 0.4);
  color: #6dba82;
  background: rgba(109, 186, 130, 0.1);
  margin-left: auto;
}

.ml-2 {
  margin-left: 0.5rem;
}
</style>
