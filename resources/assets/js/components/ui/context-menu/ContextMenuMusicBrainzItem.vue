<template>
  <template v-if="musicBrainzUrl">
    <Separator />
    <MenuItem @click="viewOnMusicBrainz">View on MusicBrainz</MenuItem>
  </template>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

const props = defineProps<{
  entityType: 'recording' | 'release' | 'artist'
  mbid?: string | null
}>()

const { MenuItem, Separator, trigger } = useContextMenu()
const { useMusicBrainz } = useThirdPartyServices()

const musicBrainzUrl = computed(() =>
  useMusicBrainz.value && props.mbid ? `https://musicbrainz.org/${props.entityType}/${props.mbid}` : null,
)

const viewOnMusicBrainz = () => trigger(() => window.open(musicBrainzUrl.value!, '_blank'))
</script>
