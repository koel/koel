import type { Ref } from 'vue'
import { computed, watch } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { themeStore } from '@/stores/themeStore'
import { getCoverThemeColors } from '@/utils/coverColor'
import { isEpisode, isRadioStation, isSong } from '@/utils/typeGuards'

const getStreamableCover = (streamable: Streamable | undefined) => {
  if (!streamable) {
    return null
  }

  if (isSong(streamable)) {
    return streamable.album_cover
  }

  if (isEpisode(streamable)) {
    return streamable.episode_image
  }

  return isRadioStation(streamable) ? streamable.logo : null
}

export const useCoverThemeColors = (currentStreamable: Ref<Streamable | undefined>) => {
  const cover = computed(() => getStreamableCover(currentStreamable.value))
  const usesChameleonTheme = computed(() => preferences.state.theme === 'chameleon')

  watch(
    [cover, usesChameleonTheme],
    async ([url, onChameleonTheme]) => {
      if (!onChameleonTheme) {
        return
      }

      if (!url) {
        themeStore.setCoverColors(null)
        return
      }

      const colors = await getCoverThemeColors(url)

      if (url === cover.value && usesChameleonTheme.value) {
        themeStore.setCoverColors(colors)
      }
    },
    { immediate: true },
  )
}
