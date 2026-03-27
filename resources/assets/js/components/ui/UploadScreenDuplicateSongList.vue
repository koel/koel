<template>
  <div class="list-wrapper">
    <h2 class="list-title">Duplicate Song Uploads</h2>
    <div v-if="duplicateSongs.length > 0">
      <ul>
        <div v-for="song in duplicateSongs" :key="song.id" class="song-wrapper">
          <strong>{{ song.title }}</strong> by {{ song.artist }}
          <input class="song-select" type="checkbox">
        </div>
      </ul>
      <button class="list-btn upload">Upload All</button>
      <button class="list-btn cancel">Cancel All</button>
    </div>
    <div v-else>
      No duplicate songs
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const duplicateSongs = ref([
  {
    title: 'Bobcaygeon',
    artist: 'Tragically Hip',
    id: 1,
  },
  {
    title: 'Random Song Name',
    artist: 'Tragically Hip',
    id: 2,
  },
  {
    title: 'Blah Blah Blah',
    artist: 'Tragically Hip',
    id: 3,
  },
])
</script>

<style scoped>
.list-wrapper {
  display: flex;
  flex-direction: column;
  justify-content: center;

  width: 100%;
  max-width: 500px;
  margin: 6rem auto;
  padding: 0.75rem;

  background-color: color-mix(in srgb, var(--color-fg), transparent 95%);

  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 12px;
  color: #ffffff;
  font-size: 0.95rem;
  transition: all 0.2s ease;
}

.list-title {
  font-size: 1.5rem;
  /* font-weight: 600; */
  text-align: center;
  color: #ffffff;
  padding: 1rem;
}

.song-wrapper {
  width: 100%;
  border: 1px solid rgba(245, 158, 11, 0.3);
  border-radius: 8px;
  padding: 0.75rem 1.25rem;
  margin: 0.5rem 0;
  font-size: 1.2rem;
}

.song-select {
  background-color: white;
}

.list-btn {
  padding: 0.5rem;
  border-radius: 8px;
  margin-right: 0.4rem;
  transition-duration: 0.2s;
}

.upload {
  background-color: rgba(31, 179, 31, 0.25);
  border: 1px solid rgba(31, 179, 31, 0.7);
}

.continue:hover {
  background-color: rgba(31, 179, 31, 0.7);
}

.cancel {
  background-color: rgba(179, 31, 31, 0.25);
  border: 1px solid rgba(179, 31, 31, 0.7);
}

.cancel:hover {
  background-color: rgba(179, 31, 31, 0.7);
}
</style>
