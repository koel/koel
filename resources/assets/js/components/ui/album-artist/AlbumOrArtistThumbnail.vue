<template>
  <button
    :style="{ backgroundImage: `url(${defaultCover})` }"
    class="thumbnail group relative w-full aspect-square bg-no-repeat bg-cover bg-center overflow-hidden rounded-md active:scale-95"
    data-testid="album-artist-thumbnail"
    @click.prevent="playOrQueue"
  >
    <img
      alt="Thumbnail"
      :src="image"
      class="w-full aspect-square object-cover"
      loading="lazy"
    >
    <span class="hidden">{{ buttonLabel }}</span>
    <span class="absolute top-0 left-0 w-full h-full group-hover:bg-black/40 no-hover:bg-black/40 z-10" />
    <span
      class="play-icon absolute flex opacity-0 no-hover:opacity-100 items-center justify-center w-[32px] aspect-square rounded-full top-1/2
      left-1/2 -translate-x-1/2 -translate-y-1/2 bg-k-highlight group-hover:opacity-100 duration-500 transition z-20"
    >
      <Icon :icon="faPlay" class="ml-0.5 text-white" size="lg" />
    </span>
  </button>
</template>

<script lang="ts" setup>
import { faPlay } from '@fortawesome/free-solid-svg-icons'
import defaultCover from '@/../img/covers/default.svg'
import { orderBy } from 'lodash'
import { computed, toRefs } from 'vue'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { playback } from '@/services/playbackManager'

const props = defineProps<{ entity: Album | Artist }>()
const { toastSuccess } = useMessageToaster()
const { go, url } = useRouter()

const { entity } = toRefs(props)

const forAlbum = computed(() => entity.value.type === 'albums')
const sortFields = computed(() => forAlbum.value ? ['disc', 'track'] : ['album_id', 'disc', 'track'])

const image = computed(() => {
  return forAlbum.value
    ? (entity.value as Album).cover || defaultCover
    : (entity.value as Artist).image || defaultCover
})

const buttonLabel = computed(() => forAlbum.value
  ? `Play all songs in the album ${entity.value.name}`
  : `Play all songs by ${entity.value.name}`,
)

const playOrQueue = async (event: MouseEvent) => {
  const songs = forAlbum.value
    ? await playableStore.fetchSongsForAlbum(entity.value as Album)
    : await playableStore.fetchSongsForArtist(entity.value as Artist)

  if (event.altKey) {
    queueStore.queue(orderBy(songs, sortFields.value))
    toastSuccess('Songs added to queue.')
    return
  }

  playback().queueAndPlay(songs)
  go(url('queue'))
}
</script>

<style lang="postcss" scoped>
.droppable {
  @apply border-2 border-dotted border-white brightness-50;

  * {
    pointer-events: none;
  }
}

.compact .icon {
  @apply text-[.3rem];
  /* to control the size of the icon */
}
</style>
