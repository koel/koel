<template>
  <template v-if="isPlus && onlySongs">
    <Separator />
    <MenuItem v-if="allHidden" @click="unhide">Unhide</MenuItem>
    <MenuItem v-else @click="hide">Hide</MenuItem>
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

const props = defineProps<{ playables: Playable[] }>()

const { MenuItem, Separator, trigger } = useContextMenu()
const { toastPromise } = useMessageToaster()
const { isPlus } = useKoelPlus()

const songs = computed(() => props.playables as Song[])
const onlySongs = computed(() => getPlayableCollectionContentType(props.playables) === 'songs')
const allHidden = computed(() => songs.value.every(({ hidden }) => hidden))

const hide = () =>
  trigger(() =>
    toastPromise(playableStore.hideSongs(songs.value), {
      loading: 'Hiding…',
      success: `${pluralize(songs.value, 'song')} hidden.`,
      error: getHttpErrorMessage,
    }),
  )

const unhide = () =>
  trigger(() =>
    toastPromise(playableStore.unhideSongs(songs.value), {
      loading: 'Unhiding…',
      success: `${pluralize(songs.value, 'song')} unhidden.`,
      error: getHttpErrorMessage,
    }),
  )
</script>
