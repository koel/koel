<template>
  <div v-if="hasLyrics" class="group relative h-full">
    <LrcLyricsPane v-if="isLrc" :font-size="fontSize" :lyrics="lrcLyrics" class="absolute inset-0 px-6 py-8" />

    <div
      v-else
      class="lyrics absolute inset-0 px-6 py-8 overflow-y-auto scroll-mask-y whitespace-pre-wrap leading-relaxed"
      data-testid="plain-text-lyrics"
    >
      {{ plainTextLyrics }}
    </div>

    <ButtonGroup :class="{ 'reveal-on-hover': !collapsible }" class="absolute top-4 right-4">
      <button v-koel-tooltip type="button" @click="zoomOut">
        <Icon :icon="faSearchMinus" fixed-width />
        <span class="sr-only">Zoom out</span>
      </button>
      <button v-koel-tooltip type="button" @click="zoomIn">
        <Icon :icon="faSearchPlus" fixed-width />
        <span class="sr-only">Zoom in</span>
      </button>
      <button v-if="expandable" v-koel-tooltip type="button" @click="openLyricsOverlay">
        <Icon :icon="faUpRightAndDownLeftFromCenter" fixed-width />
        <span class="sr-only">Show lyrics full screen</span>
      </button>
      <button v-if="collapsible" v-koel-tooltip type="button" @click="closeLyricsOverlay">
        <Icon :icon="faDownLeftAndUpRightToCenter" fixed-width />
        <span class="sr-only">Close lyrics</span>
      </button>
    </ButtonGroup>
  </div>
  <p v-else class="px-6 py-8">
    <template v-if="userCanUpdateLyrics">
      No lyrics found.
      <LinkButton @click.prevent="showEditSongForm"> Click here </LinkButton>
      to add lyrics.
    </template>
    <span v-else>No lyrics available. Are you listening to Bach?</span>
  </p>
</template>

<script lang="ts" setup>
import {
  faDownLeftAndUpRightToCenter,
  faSearchMinus,
  faSearchPlus,
  faUpRightAndDownLeftFromCenter,
} from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRefs, watch } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { useLyrics } from '@/composables/useLyrics'
import { useModal } from '@/composables/useModal'
import { useLyricsOverlay } from '@/composables/useLyricsOverlay'

import ButtonGroup from '@/components/ui/ButtonGroup.vue'
import LinkButton from '@/components/ui/form/LinkButton.vue'

const props = withDefaults(defineProps<{ song: Song; expandable?: boolean; collapsible?: boolean }>(), {
  expandable: false,
  collapsible: false,
})
const LrcLyricsPane = defineAsyncComponent(() => import('@/components/ui/lyrics/LrcLyricsPane.vue'))
const EditSongForm = defineAsyncComponent(() => import('@/components/playable/EditSongForm.vue'))

const { openModal } = useModal()
const { openLyricsOverlay, closeLyricsOverlay } = useLyricsOverlay()
const { song } = toRefs(props)
const zoomLevel = ref(preferences.lyrics_zoom_level || 1)

const { plainTextLyrics, lrcLyrics, isLrc, hasLyrics, userCanUpdateLyrics } = useLyrics(song)

const fontSize = computed(() => `${1 + (zoomLevel.value - 1) * 0.2}em`)

const zoomIn = () => (zoomLevel.value = Math.min(zoomLevel.value + 1, 8))
const zoomOut = () => (zoomLevel.value = Math.max(zoomLevel.value - 1, -2))
const showEditSongForm = () => openModal<'EDIT_SONG_FORM'>(EditSongForm, { songs: [song.value], initialTab: 'lyrics' })

watch(zoomLevel, level => (preferences.lyrics_zoom_level = level), { immediate: true })
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

.reveal-on-hover {
  @apply opacity-0 group-hover:opacity-50 group-focus-within:opacity-100! hover:opacity-100! transition-opacity no-hover:opacity-100!;
}

.lyrics {
  font-size: v-bind(fontSize);
}
</style>
