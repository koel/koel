<template>
  <input
    v-model="mutatedPlaylist.name"
    v-koel-focus
    data-testid="inline-playlist-name-input"
    name="name"
    required
    type="text"
    @blur="update"
    @keyup.esc="cancel"
    @keyup.enter="update"
  >
</template>

<script lang="ts" setup>
import { reactive, ref, toRefs } from 'vue'
import { playlistStore } from '@/stores'
import { alerts } from '@/utils'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const updating = ref(false)

const mutatedPlaylist = reactive<Playlist>(Object.assign({}, playlist.value))

const emit = defineEmits(['updated', 'cancelled'])

const update = async () => {
  mutatedPlaylist.name = mutatedPlaylist.name.trim()

  if (!mutatedPlaylist.name) {
    cancel()
    return
  }

  if (mutatedPlaylist.name === playlist.value.name) {
    cancel()
    return
  }

  // prevent duplicate updating from Enter and Blur
  if (updating.value) {
    return
  }

  updating.value = true

  await playlistStore.update(mutatedPlaylist)
  alerts.success(`Updated playlist "${mutatedPlaylist.name}."`)
  emit('updated', mutatedPlaylist)
}

const cancel = () => emit('cancelled')
</script>
