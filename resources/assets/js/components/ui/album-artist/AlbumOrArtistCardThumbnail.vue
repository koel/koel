<template>
  <div
    class="card-thumbnail group/thumb relative w-full aspect-square rounded-xl overflow-hidden bg-cover bg-center bg-no-repeat text-k-fg"
    :style="{ backgroundImage: `url(${defaultCover})` }"
    data-testid="album-artist-card-thumbnail"
  >
    <img
      v-if="image"
      :src="image"
      :alt="entity.name"
      class="absolute inset-0 w-full h-full object-cover"
      loading="lazy"
    />

    <div
      class="overlay absolute inset-0 z-10 bg-black/60 opacity-0 group-hover/thumb:opacity-100 no-hover:opacity-100 transition-opacity duration-200"
    />

    <FavoriteButton
      :favorite="entity.favorite"
      size="md"
      class="absolute top-3 left-4 z-20 transition-opacity"
      :class="
        entity.favorite
          ? 'opacity-100 drop-shadow-[0_1px_4px_var(--k-bg-50)]'
          : 'opacity-0 group-hover/thumb:opacity-100 no-hover:opacity-100'
      "
      @toggle="emit('toggle-favorite')"
    />

    <StarRating
      :rateable="entity"
      size="sm"
      class="absolute top-3 right-4 z-20 transition-opacity"
      :class="
        entity.rating > 0
          ? 'opacity-100 drop-shadow-[0_1px_4px_var(--k-bg-50)]'
          : 'opacity-0 group-hover/thumb:opacity-100 no-hover:opacity-100'
      "
    />

    <button
      type="button"
      class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-20 opacity-0 group-hover/thumb:opacity-100 no-hover:opacity-100 transition-opacity"
      :title="playLabel"
      @click.stop="playOrQueue"
    >
      <span
        class="play-icon flex items-center justify-center w-[32px] aspect-square rounded-full bg-k-highlight text-k-highlight-fg"
        aria-hidden="true"
      >
        <Icon :icon="faPlay" size="lg" class="ml-0.5" />
      </span>
      <span class="sr-only">{{ playLabel }}</span>
    </button>

    <button
      type="button"
      class="absolute bottom-3 right-4 z-20 p-1 opacity-0 group-hover/thumb:opacity-100 no-hover:opacity-100 hover:scale-110 transition-[opacity,transform]"
      title="More actions"
      @click.stop="emit('context-menu', $event)"
    >
      <Icon :icon="faEllipsis" />
      <span class="sr-only">More actions</span>
    </button>
  </div>
</template>

<script lang="ts" setup>
import { orderBy } from 'lodash-es'
import { faEllipsis, faPlay } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { playback } from '@/services/playbackManager'
import { useBranding } from '@/composables/useBranding'

import FavoriteButton from '@/components/ui/FavoriteButton.vue'
import StarRating from '@/components/ui/StarRating.vue'

const props = defineProps<{ entity: Album | Artist }>()
const { entity } = toRefs(props)

const emit = defineEmits<{
  (e: 'toggle-favorite'): void
  (e: 'context-menu', event: MouseEvent): void
}>()

const { toastSuccess } = useMessageToaster()
const { go, url } = useRouter()
const { cover: defaultCover } = useBranding()

const forAlbum = computed(() => entity.value.type === 'albums')

const image = computed(() =>
  forAlbum.value ? (entity.value as Album).cover || defaultCover : (entity.value as Artist).image || defaultCover,
)

const playLabel = computed(() =>
  forAlbum.value ? `Play all songs in the album ${entity.value.name}` : `Play all songs by ${entity.value.name}`,
)

const sortFields = computed(() => (forAlbum.value ? ['disc', 'track'] : ['album_id', 'disc', 'track']))

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
