<template>
  <div class="dup-wrap">
    <div class="dup-header">
      <FontAwesomeIcon :icon="faWarning" class="warning-icon" />
      <span class="dup-header-label">{{ songs.length }} Duplicated song{{ songs.length !== 1 ? 's' : '' }}</span>
    </div>

    <div v-for="song in songs" :key="song.id" class="dup-row">
      <div class="dup-icon">
        <FontAwesomeIcon :icon="faMusic" />
      </div>
      <span class="dup-name">{{ song.filename }}</span>
      <button class="dup-badge" @click="keepDuplicates([song])">
        <FontAwesomeIcon :icon="faCheck" />
        Duplicate
      </button>
      <button class="btn-discard" @click="deleteDuplicates([song])">
        <FontAwesomeIcon :icon="faX" />
        Discard
      </button>
    </div>

    <div class="dup-footer">
      <button class="btn-discard-all" @click="deleteDuplicates(songs)">
        <FontAwesomeIcon :icon="faX" />
        Discard all
      </button>
      <button class="btn-upload-all" @click="keepDuplicates(songs)">
        <FontAwesomeIcon :icon="faUpload" />
        Upload all anyway
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { DuplicateUpload } from '@/services/uploadService'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUpload, faX, faWarning, faMusic, faCheck } from '@fortawesome/free-solid-svg-icons'
import { useDuplicateUploads } from '@/composables/useDuplicateUploads'

defineProps<{
  songs: DuplicateUpload[]
}>()

const { keepDuplicates, deleteDuplicates } = useDuplicateUploads()
</script>

<style scoped>
.dup-wrap {
  background: #1a1a1a;
  border-radius: 8px;
  overflow: hidden;
  font-family: inherit;
}
.dup-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.07);
}
.warning-icon {
  color: #f59e0b;
}
.dup-header-icon {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
  color: #f59e0b;
}
.dup-header-label {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #a89060;
  flex: 1;
}
.dup-header-count {
  font-size: 10px;
  color: #666;
}
.dup-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 14px;
  border-bottom: 0.5px solid rgba(255, 255, 255, 0.05);
  transition: background 0.15s;
}
.dup-row:hover {
  background: rgba(255, 255, 255, 0.04);
}
.dup-icon {
  width: 28px;
  height: 28px;
  border-radius: 5px;
  background: rgba(186, 117, 23, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #c99040;
}
.dup-icon svg {
  width: 13px;
  height: 13px;
}
.dup-name {
  font-size: 12.5px;
  color: #c8c8c8;
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dup-badge {
  font-size: 10px;
  padding: 2px 7px;
  border-radius: 4px;
  background: none;
  border: 0.5px solid #6dba82;
  color: #6dba82;
  letter-spacing: 0.03em;
  flex-shrink: 0;
  cursor: pointer;
}
.dup-badge:hover {
  background-color: rgb(109, 186, 130, 0.2);
}
.btn-discard {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: 0.5px solid #e06060;
  border-radius: 5px;
  color: #e06060;
  font-size: 11px;
  padding: 3px 8px;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.btn-discard:hover {
  background: rgba(220, 60, 60, 0.12);
  border-color: rgba(220, 60, 60, 0.35);
  color: #e06060;
}
.btn-discard svg {
  width: 10px;
  height: 10px;
}
.dup-footer {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-top: 0.5px solid rgba(255, 255, 255, 0.07);
  background: rgba(0, 0, 0, 0.2);
}
.btn-discard-all {
  display: flex;
  align-items: center;
  gap: 5px;
  background: none;
  border: 0.5px solid rgba(220, 60, 60, 0.3);
  border-radius: 6px;
  color: #c06060;
  font-size: 11.5px;
  padding: 5px 12px;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-discard-all:hover {
  background: rgba(220, 60, 60, 0.12);
  border-color: rgba(220, 60, 60, 0.5);
  color: #e06060;
}
.btn-upload-all {
  display: flex;
  align-items: center;
  gap: 5px;
  background: rgba(76, 175, 100, 0.12);
  border: 0.5px solid rgba(76, 175, 100, 0.3);
  border-radius: 6px;
  color: #6dba82;
  font-size: 11.5px;
  padding: 5px 12px;
  cursor: pointer;
  transition: all 0.15s;
  margin-left: auto;
}
.btn-upload-all:hover {
  background: rgba(76, 175, 100, 0.2);
  border-color: rgba(76, 175, 100, 0.5);
  color: #80d098;
}
.btn-discard-all svg,
.btn-upload-all svg {
  width: 12px;
  height: 12px;
}
</style>
