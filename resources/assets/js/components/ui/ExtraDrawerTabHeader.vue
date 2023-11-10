<template>
  <button
    id="extraTabLyrics"
    v-koel-tooltip.left
    :class="{ active: value === 'Lyrics' }"
    title="Lyrics"
    type="button"
    @click.prevent="toggleTab('Lyrics')"
  >
    <Icon :icon="faFeather" fixed-width />
  </button>
  <button
    id="extraTabArtist"
    v-koel-tooltip.left
    :class="{ active: value === 'Artist' }"
    title="Artist information"
    type="button"
    @click.prevent="toggleTab('Artist')"
  >
    <Icon :icon="faMicrophone" fixed-width />
  </button>
  <button
    id="extraTabAlbum"
    v-koel-tooltip.left
    :class="{ active: value === 'Album' }"
    title="Album information"
    type="button"
    @click.prevent="toggleTab('Album')"
  >
    <Icon :icon="faCompactDisc" fixed-width />
  </button>
  <button
    v-if="useYouTube"
    id="extraTabYouTube"
    v-koel-tooltip.left
    :class="{ active: value === 'YouTube' }"
    title="Related YouTube videos"
    type="button"
    @click.prevent="toggleTab('YouTube')"
  >
    <Icon :icon="faYoutube" fixed-width />
  </button>
</template>

<script lang="ts" setup>
import { faCompactDisc, faFeather, faMicrophone } from '@fortawesome/free-solid-svg-icons'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { computed } from 'vue'
import { useThirdPartyServices } from '@/composables'

const props = withDefaults(defineProps<{ modelValue?: ExtraPanelTab | null }>(), {
  modelValue: null
})

const emit = defineEmits<{ (e: 'update:modelValue', value: ExtraPanelTab | null): void }>()

const { useYouTube } = useThirdPartyServices()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})

const toggleTab = (tab: ExtraPanelTab) => (value.value = value.value === tab ? null : tab)
</script>

<style scoped>

</style>
