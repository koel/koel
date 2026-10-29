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
import { getHttpErrorMessage } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { usePolicies } from '@/composables/usePolicies'

const props = defineProps<{ playables: Playable[] }>()

const { MenuItem, Separator, trigger } = useContextMenu()
const { toastPromise } = useMessageToaster()
const { currentUserCan } = usePolicies()
const { isPlus } = useKoelPlus()

const songs = computed(() => props.playables as Song[])
const onlySongs = computed(() => getPlayableCollectionContentType(props.playables) === 'songs')

const makePublic = () =>
  trigger(() =>
    toastPromise(playableStore.publicizeSongs(songs.value), {
      loading: 'Unmarking as private…',
      success: `Unmarked ${pluralize(songs.value, 'song')} as private.`,
      error: getHttpErrorMessage,
    }),
  )

const makePrivate = () =>
  trigger(() =>
    toastPromise(playableStore.privatizeSongs(songs.value), {
      loading: 'Marking as private…',
      success: `Marked ${pluralize(songs.value, 'song')} as private.`,
      warning: privatizedIds => {
        if (!privatizedIds.length) {
          return 'Songs cannot be marked as private if they’re part of a collaborative playlist.'
        }

        if (privatizedIds.length < songs.value.length) {
          return 'Some songs cannot be marked as private as they’re part of a collaborative playlist.'
        }
      },
      error: getHttpErrorMessage,
    }),
  )

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
