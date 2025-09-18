<template>
  <section v-if="embed && !error" class="max-h-screen flex flex-col overflow-hidden">
    <Banner :embed :options>
      <template #audio-player>
        <AudioPlayer ref="player" :playables="embed.playables" :preview="options.preview" />
      </template>
    </Banner>
    <TrackList v-if="showTrackList" :playables="embed.playables" @play="onItemPlayRequested" />
  </section>

  <ErrorMessage v-else-if="error" />
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, onMounted, ref } from 'vue'
import { embedService } from '@/stores/embedService'
import { useRouter } from '@/composables/useRouter'
import { themeStore } from '@/stores/themeStore'

withDefaults(defineProps<{ preview?: boolean }>(), {
  preview: false,
})

const AudioPlayer = defineAsyncComponent(() => import('@/components/embed/widget/audio-player/EmbedAudioPlayer.vue'))
const Banner = defineAsyncComponent(() => import('@/components/embed/widget/EmbedWidgetBanner.vue'))
const TrackList = defineAsyncComponent(() => import('@/components/embed/widget/EmbedWidgetTrackList.vue'))
const ErrorMessage = defineAsyncComponent(() => import('@/components/embed/widget/EmbedWidgetErrorMessage.vue'))

const { getRouteParam } = useRouter()

const player = ref<InstanceType<typeof AudioPlayer>>()
const embed = ref<WidgetReadyEmbed>()

const options = ref<EmbedOptions>({
  theme: themeStore.getDefaultTheme().id,
  layout: 'full',
  preview: false,
})

const showTrackList = computed(() => {
  if (!embed.value) {
    return false
  }

  return options.value.layout !== 'compact'
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

const error = ref<unknown>(null)
const loading = ref(false)

const getPayload = async (id: string, encryptedOptions: string) => {
  loading.value = true

  try {
    const payload = await embedService.getWidgetPayload(id, encryptedOptions)

    options.value = payload.options
    embed.value = payload.embed

    themeStore.init(options.value.theme)
  } catch (e: unknown) {
    error.value = e
    return
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await getPayload(getRouteParam('id'), getRouteParam('options'))

  // reload after every 24 hours to refresh the signed urls
  setInterval(() => getPayload(getRouteParam('id'), getRouteParam('options')), 24 * 60 * 60 * 1000)
})
</script>
