<template>
  <ExtraDrawerButton
    id="extraTabLyrics"
    v-koel-tooltip.left
    :class="{ active: value === 'Lyrics' }"
    title="Lyrics"
    @click.prevent="toggleTab('Lyrics')"
  >
    <Icon :icon="faFeather" fixed-width />
  </ExtraDrawerButton>
  <ExtraDrawerButton
    id="extraTabArtist"
    v-koel-tooltip.left
    :class="{ active: value === 'Artist' }"
    title="Artist information"
    @click.prevent="toggleTab('Artist')"
  >
    <Icon :icon="faMicrophone" fixed-width />
  </ExtraDrawerButton>
  <ExtraDrawerButton
    id="extraTabAlbum"
    v-koel-tooltip.left
    :class="{ active: value === 'Album' }"
    title="Album information"
    @click.prevent="toggleTab('Album')"
  >
    <Icon :icon="faCompactDisc" fixed-width />
  </ExtraDrawerButton>
  <ExtraDrawerButton
    v-if="useYouTube"
    id="extraTabYouTube"
    v-koel-tooltip.left
    :class="{ active: value === 'YouTube' }"
    title="Related YouTube videos"
    @click.prevent="toggleTab('YouTube')"
  >
    <Icon :icon="faYoutube" fixed-width />
  </ExtraDrawerButton>
</template>

<script lang="ts" setup>
import { faCompactDisc, faFeather, faMicrophone } from '@fortawesome/free-solid-svg-icons'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { computed } from 'vue'
import { useThirdPartyServices } from '@/composables'
import ExtraDrawerButton from '@/components/layout/main-wrapper/extra-drawer/ExtraDrawerButton.vue'

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
