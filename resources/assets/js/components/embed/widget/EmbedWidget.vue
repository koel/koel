<template>
  <section v-if="embed" class="max-h-screen flex flex-col overflow-hidden">
    <header class="flex h-[150px] p-6 gap-5 bg-white/5 flex-shrink-0 justify-end">
      <aside class="size-[112px] aspect-square">
        <EmbedThumbnail :embeddable="embed.embeddable" />
      </aside>

      <div class="flex-1 flex flex-col justify-end gap-3">
        <h3
          class="text-3xl flex items-center gap-2 font-medium sm:text-4xl sm:font-bold line-clamp-1"
          :title="String(attributes!.title)"
        >
          <span
            v-if="options.preview"
            class="text-xs uppercase font-semibold bg-white/10 rounded px-[5px] py-[1px] mt-[5px] border border-px border-white/10"
          >
            Preview
          </span>
          <span>{{ attributes!.title }}</span>
        </h3>

        <p v-if="attributes?.subtitle" class="text-k-text-secondary line-clamp-1" :title="attributes.subtitle">
          {{ attributes.subtitle }}
        </p>

        <EmbedAudioPlayer ref="player" :playables="embed.playables" :preview="options.preview" />
      </div>

      <span class="absolute right-3 top-3 size-10 p-1 bg-white/20 rounded-md">
        <img alt="Koel's logo" :src="logo">
      </span>
    </header>

    <main v-if="showPlayableList" class="relative flex flex-col overflow-scroll flex-1">
      <div class="playable-list-wrap relative flex flex-col flex-1 overflow-auto p-2">
        <VirtualScroller
          v-slot="{ item }: { item: PlayableRow }"
          :item-height="64"
          :items="rows"
        >
          <PlayableEmbedItem :key="item.playable.id" :item @play="onItemPlayRequested(item.playable)" />
        </VirtualScroller>
      </div>
    </main>
  </section>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, reactive, ref } from 'vue'
import { embedService } from '@/stores/embedService'
import { useRouter } from '@/composables/useRouter'
import { getPlayableProp } from '@/utils/helpers'
import { logger } from '@/utils/logger'
import { themeStore } from '@/stores/themeStore'
import logo from '@/../img/logo.svg'

import EmbedThumbnail from '@/components/embed/widget/EmbedThumbnail.vue'

withDefaults(defineProps<{ preview?: boolean }>(), {
  preview: false,
})

const VirtualScroller = defineAsyncComponent(() => import('@/components/ui/VirtualScroller.vue'))
const PlayableEmbedItem = defineAsyncComponent(() => import('@/components/embed/widget/PlayableEmbedItem.vue'))
const EmbedAudioPlayer = defineAsyncComponent(() => import('@/components/embed/widget/audio-player/EmbedAudioPlayer.vue'))

interface Attributes {
  title: string | null
  subtitle: string | null
}

const { getRouteParam, triggerNotFound } = useRouter()

const player = ref<InstanceType<typeof EmbedAudioPlayer>>()
const embed = ref<WidgetReadyEmbed>()

const options = ref<EmbedOptions>({
  theme: themeStore.getDefaultTheme().id,
  layout: 'full',
  preview: false,
})

const showPlayableList = computed(() => {
  if (!embed.value) {
    return false
  }

  return options.value.layout !== 'compact'
})

const rows = computed(() => {
  return embed.value?.playables.map<PlayableRow>(playable => reactive({
    playable,
    selected: false,
  })) || []
})

const onItemPlayRequested = (playable: Playable) => {
  switch (playable.playback_state) {
    case 'Playing':
      player.value?.pause()
      break
    case 'Paused':
      player.value?.resume()
      break
    default:
      player.value?.play(playable)
      break
  }
}

const loading = ref(false)

const getPayload = async (id: string, encryptedOptions: string) => {
  loading.value = true

  try {
    const payload = await embedService.getWidgetPayload(id, encryptedOptions)

    options.value = payload.options
    embed.value = payload.embed

    themeStore.init(options.value.theme)
  } catch (e: unknown) {
    logger.error(e)
    await triggerNotFound()
    return
  } finally {
    loading.value = false
  }
}

const attributes = computed(() => {
  if (!embed.value) {
    return null
  }

  const attrs: Attributes = {
    title: null,
    subtitle: null,
  }

  switch (embed.value.embeddable_type) {
    case 'album':
      attrs.title = (embed.value.embeddable as Album).name
      attrs.subtitle = `Album by ${(embed.value.embeddable as Album).artist_name}`
      break
    case 'artist':
      attrs.title = (embed.value.embeddable as Artist).name
      attrs.subtitle = 'Artist'
      break
    case 'playable':
      const playable = embed.value.embeddable as Playable
      attrs.title = getPlayableProp(playable, 'title', 'title')
      attrs.subtitle = getPlayableProp(playable, 'artist_name', 'podcast_title')
      break
    case 'playlist':
      const playlist = embed.value.embeddable as Playlist
      attrs.title = playlist.name
      attrs.subtitle = playlist.description || 'Playlist'
      break
  }

  return attrs
})

onMounted(async () => {
  await getPayload(getRouteParam('id'), getRouteParam('options'))

  // reload after every 24 hours to refresh the signed urls
  setInterval(() => getPayload(getRouteParam('id'), getRouteParam('options')), 24 * 60 * 60 * 1000)
})
</script>

<style scoped lang="postcss">
.playable-list-wrap {
  .virtual-scroller {
    @apply flex-1;
  }
}
</style>
