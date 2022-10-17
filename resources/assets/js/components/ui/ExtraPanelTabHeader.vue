<template>
  <button
    id="extraTabLyrics"
    :class="{ active: value === 'Lyrics' }"
    title="Lyrics"
    type="button"
    @click.prevent="toggleTab('Lyrics')"
  >
    <icon :icon="faFileLines" fixed-width/>
  </button>
  <button
    id="extraTabArtist"
    :class="{ active: value === 'Artist' }"
    title="Artist information"
    type="button"
    @click.prevent="toggleTab('Artist')"
  >
    <icon :icon="faMicrophone" fixed-width/>
  </button>
  <button
    id="extraTabAlbum"
    :class="{ active: value === 'Album' }"
    title="Album information"
    type="button"
    @click.prevent="toggleTab('Album')"
  >
    <icon :icon="faCompactDisc" fixed-width/>
  </button>
  <button
    v-if="useYouTube"
    id="extraTabYouTube"
    :class="{ active: value === 'YouTube' }"
    title="Related YouTube videos"
    type="button"
    @click.prevent="toggleTab('YouTube')"
  >
    <icon :icon="faYoutube" fixed-width/>
  </button>
</template>

<script lang="ts" setup>
import { faCompactDisc, faFileLines, faMicrophone } from '@fortawesome/free-solid-svg-icons'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { computed } from 'vue'
import { useThirdPartyServices } from '@/composables'

const props = defineProps<{ modelValue?: ExtraPanelTab }>()

const emit = defineEmits(['update:modelValue'])

const { useYouTube } = useThirdPartyServices()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})

const toggleTab = (tab: ExtraPanelTab) => (value.value = value.value === tab ? undefined : tab)
</script>

<style scoped>

</style>
