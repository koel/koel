<template>
  <SettingGroup>
    <template #title>Hidden Songs</template>
    <template #subtitle>Hidden songs are left out of shuffles, suggestions, and smart playlists.</template>

    <p v-if="loading" class="text-k-fg-50">Loading…</p>
    <p v-else-if="!songs.length" class="text-k-fg-50">You haven’t hidden any songs.</p>
    <ul v-else class="divide-y divide-k-fg-10">
      <HiddenSongListItem v-for="song in songs" :key="song.id" :song @unhidden="removeFromList" />
    </ul>
  </SettingGroup>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { playableStore } from '@/stores/playableStore'
import { useErrorHandler } from '@/composables/useErrorHandler'

import SettingGroup from '@/components/screens/settings/SettingGroup.vue'
import HiddenSongListItem from '@/components/profile-preferences/HiddenSongListItem.vue'

const songs = ref<Song[]>([])
const loading = ref(true)

const { handleHttpError } = useErrorHandler()

const removeFromList = (song: Song) => (songs.value = songs.value.filter(({ id }) => id !== song.id))

onMounted(async () => {
  try {
    songs.value = await playableStore.fetchHiddenSongs()
  } catch (error: unknown) {
    handleHttpError(error)
  } finally {
    loading.value = false
  }
})
</script>
