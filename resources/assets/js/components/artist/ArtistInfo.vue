<template>
  <AlbumArtistInfo :mode="mode" data-testid="artist-info">
    <template #header>{{ artist.name }}</template>

    <template #art>
      <ArtistThumbnail :entity="artist" class="group" />
    </template>

    <ParagraphSkeleton v-if="loading" />

    <div v-if="!loading && info?.bio" v-html="info.bio.full" />

    <template v-if="info && !loading" #footer>
      <a :href="info.url" rel="openener" target="_blank">Source</a>
    </template>
  </AlbumArtistInfo>
</template>

<script lang="ts" setup>
import { ref, toRefs, watch } from 'vue'
import { encyclopediaService } from '@/services/encyclopediaService'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

import ArtistThumbnail from '@/components/ui/album-artist/AlbumOrArtistThumbnail.vue'
import AlbumArtistInfo from '@/components/ui/album-artist/AlbumOrArtistInfo.vue'
import ParagraphSkeleton from '@/components/ui/ParagraphSkeleton.vue'

const props = withDefaults(defineProps<{ artist: Artist, mode?: EncyclopediaDisplayMode }>(), { mode: 'aside' })
const { artist, mode } = toRefs(props)

const { useMusicBrainz, useLastfm, useSpotify } = useThirdPartyServices()

const loading = ref(false)
const info = ref<ArtistInfo | null>(null)

watch(artist, async () => {
  info.value = null

  if (useMusicBrainz.value || useLastfm.value || useSpotify.value) {
    loading.value = true
    info.value = await encyclopediaService.fetchForArtist(artist.value)
    loading.value = false
  }
}, { immediate: true })
</script>

<style lang="postcss" scoped>
:deep(.play-icon) {
  @apply scale-[3];
}
</style>
