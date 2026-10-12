<template>
  <template v-if="musicBrainzUrl">
    <Separator />
    <MenuItem @click="viewOnMusicBrainz">View on MusicBrainz</MenuItem>
  </template>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { isSong } from '@/utils/typeGuards'
import { useContextMenu } from '@/composables/useContextMenu'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'

const props = defineProps<{ playables: Playable[] }>()

const { MenuItem, Separator, trigger } = useContextMenu()
const { useMusicBrainz } = useThirdPartyServices()

const musicBrainzUrl = computed(() => {
  if (!useMusicBrainz.value || props.playables.length !== 1 || !isSong(props.playables[0])) {
    return null
  }

  const { mbid } = props.playables[0]

  return mbid ? `https://musicbrainz.org/recording/${mbid}` : null
})

const viewOnMusicBrainz = () => trigger(() => window.open(musicBrainzUrl.value!, '_blank'))
</script>
