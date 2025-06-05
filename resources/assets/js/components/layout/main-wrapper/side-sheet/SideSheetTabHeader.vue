<template>
  <SideSheetButton
    id="extraTabLyrics"
    v-koel-tooltip.left
    :class="{ active: value === 'Lyrics' }"
    data-testid="side-sheet-lyrics-tab-header"
    role="tab"
    title="Lyrics"
    @click.prevent="toggleTab('Lyrics')"
  >
    <Icon :icon="faFeather" fixed-width />
  </SideSheetButton>
  <SideSheetButton
    id="extraTabArtist"
    v-koel-tooltip.left
    :class="{ active: value === 'Artist' }"
    data-testid="side-sheet-artist-tab-header"
    role="tab"
    title="Artist information"
    @click.prevent="toggleTab('Artist')"
  >
    <MicVocalIcon size="18" />
  </SideSheetButton>
  <SideSheetButton
    id="extraTabAlbum"
    v-koel-tooltip.left
    :class="{ active: value === 'Album' }"
    data-testid="side-sheet-album-tab-header"
    role="tab"
    title="Album information"
    @click.prevent="toggleTab('Album')"
  >
    <Icon :icon="faCompactDisc" fixed-width />
  </SideSheetButton>
  <SideSheetButton
    v-if="useYouTube"
    id="extraTabYouTube"
    v-koel-tooltip.left
    :class="{ active: value === 'YouTube' }"
    data-testid="side-sheet-youtube-tab-header"
    role="tab"
    title="Related YouTube videos"
    @click.prevent="toggleTab('YouTube')"
  >
    <Icon :icon="faYoutube" fixed-width />
  </SideSheetButton>
</template>

<script lang="ts" setup>
import { faCompactDisc, faFeather } from '@fortawesome/free-solid-svg-icons'
import { MicVocalIcon } from 'lucide-vue-next'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { computed } from 'vue'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

import SideSheetButton from '@/components/layout/main-wrapper/side-sheet/SideSheetButton.vue'

const props = withDefaults(defineProps<{ modelValue?: SideSheetTab | null }>(), {
  modelValue: null,
})

const emit = defineEmits<{ (e: 'update:modelValue', value: SideSheetTab | null): void }>()

const { useYouTube } = useThirdPartyServices()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
})

const toggleTab = (tab: SideSheetTab) => (value.value = value.value === tab ? null : tab)
</script>
