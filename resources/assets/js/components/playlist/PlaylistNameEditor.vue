<template>
  <input
    v-model="name"
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
import { logger, requireInjection } from '@/utils'
import { DialogBoxKey, MessageToasterKey } from '@/symbols'

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)
const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

let updating = false

const mutablePlaylist = reactive<Playlist>(Object.assign({}, playlist.value))
const name = ref(mutablePlaylist.name)

const emit = defineEmits(['updated', 'cancelled'])

const update = async () => {
  if (!name.value || name.value === playlist.value.name) {
    cancel()
    return
  }

  // prevent duplicate updating from Enter and Blur
  if (updating) {
    return
  }

  updating = true

  try {
    await playlistStore.update(mutablePlaylist, { name: name.value })
    toaster.value.success(`Playlist "${name.value}" updated.`)
    emit('updated', name.value)
  } catch (error) {
    dialog.value.error('Something went wrong. Please try again.')
    logger.error(error)
  } finally {
    updating = false
  }
}

const cancel = () => {
  name.value = playlist.value.name
  emit('cancelled')
}
</script>
