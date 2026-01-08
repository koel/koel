<template>
  <div v-if="hasLyrics" class="group relative h-full">
    <LrcLyricsPane
      v-if="isLrc"
      :font-size="fontSize"
      :lyrics="lrcLyrics"
      class="absolute inset-0 px-6 py-8"
    />

    <div
      v-else
      class="lyrics px-6 py-8 whitespace-pre-wrap leading-relaxed"
      data-testid="plain-text-lyrics"
    >
      {{ plainTextLyrics }}
    </div>

    <Magnifier
      class="absolute top-4 right-4 opacity-0 group-hover:opacity-50 hover:!opacity-100 transition-opacity no-hover:!opacity-100"
      @in="zoomIn"
      @out="zoomOut"
    />
  </div>
  <p v-else class="px-6 py-8">
    <template v-if="userCanUpdateLyrics">
      {{ t('misc.noLyricsFound') }}
      <a role="button" @click.prevent="showEditSongForm">{{ t('misc.clickHere') }}</a>
      {{ t('misc.addLyrics') }}
    </template>
    <span v-else>{{ t('misc.noLyricsAvailable') }}</span>
  </p>
</template>

<script lang="ts" setup>
import { computed, ref, toRefs, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { eventBus } from '@/utils/eventBus'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { useLyrics } from '@/composables/useLyrics'

const { t } = useI18n()
const props = defineProps<{ song: Song }>()
const LrcLyricsPane = defineAsyncComponent(() => import('@/components/ui/lyrics/LrcLyricsPane.vue'))
const Magnifier = defineAsyncComponent(() => import('@/components/ui/Magnifier.vue'))

const { song } = toRefs(props)
const zoomLevel = ref(preferences.lyrics_zoom_level || 1)

const { plainTextLyrics, lrcLyrics, isLrc, hasLyrics, userCanUpdateLyrics } = useLyrics(song)

const fontSize = computed(() => `${1 + (zoomLevel.value - 1) * 0.2}rem`)

const zoomIn = () => (zoomLevel.value = Math.min(zoomLevel.value + 1, 8))
const zoomOut = () => (zoomLevel.value = Math.max(zoomLevel.value - 1, -2))
const showEditSongForm = () => eventBus.emit('MODAL_SHOW_EDIT_SONG_FORM', song.value, 'lyrics')

watch(zoomLevel, level => (preferences.lyrics_zoom_level = level), { immediate: true })
</script>

<style lang="postcss" scoped>
.lyrics {
  font-size: v-bind(fontSize);
}
</style>
