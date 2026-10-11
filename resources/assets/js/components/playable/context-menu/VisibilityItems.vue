<template>
  <template v-if="visibilityActions.length">
    <Separator />
    <MenuItem v-for="{ label, handler } in visibilityActions" :key="label" @click="handler">
      {{ label }}
    </MenuItem>
  </template>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { pluralize } from '@/utils/formatters'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { playableStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { usePolicies } from '@/composables/usePolicies'

const props = defineProps<{ playables: Playable[] }>()

const { MenuItem, Separator, trigger } = useContextMenu()
const { toastSuccess, toastError, toastWarning } = useMessageToaster()
const { currentUserCan } = usePolicies()
const { isPlus } = useKoelPlus()

const songs = computed(() => props.playables as Song[])
const onlySongs = computed(() => getPlayableCollectionContentType(props.playables) === 'songs')

const makePublic = () =>
  trigger(async () => {
    await playableStore.publicizeSongs(songs.value)
    toastSuccess(`Unmarked ${pluralize(songs.value, 'song')} as private.`)
  })

const makePrivate = () =>
  trigger(async () => {
    const privatizedIds = await playableStore.privatizeSongs(songs.value)

    if (!privatizedIds.length) {
      toastError('Songs cannot be marked as private if they’re part of a collaborative playlist.')
      return
    }

    if (privatizedIds.length < songs.value.length) {
      toastWarning('Some songs cannot be marked as private as they’re part of a collaborative playlist.')
      return
    }

    toastSuccess(`Marked ${pluralize(songs.value, 'song')} as private.`)
  })

const visibilityActions = computed(() => {
  if (!onlySongs.value || !currentUserCan.editSong(songs.value) || !isPlus.value) {
    return []
  }

  const visibilities = Array.from(new Set(songs.value.map(song => (song.is_public ? 'public' : 'private'))))

  if (visibilities.length === 2) {
    return [
      { label: 'Unmark as Private', handler: makePublic },
      { label: 'Mark as Private', handler: makePrivate },
    ]
  }

  return visibilities[0] === 'public'
    ? [{ label: 'Mark as Private', handler: makePrivate }]
    : [{ label: 'Unmark as Private', handler: makePublic }]
})
</script>
