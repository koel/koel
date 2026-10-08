import { computed, onScopeDispose, reactive, readonly, ref, watchEffect } from 'vue'
import { useBranding } from '@/composables/useBranding'
import { useRouter } from '@/composables/useRouter'

type ScreenTitleGetter = () => string | null | undefined

const nowPlayingTitle = ref<string | null>(null)
const screenTitleGetters = reactive(new Map<ScreenName, ScreenTitleGetter>())

export const usePageTitle = () => {
  const brandName = useBranding().name

  const setNowPlayingTitle = (title: string | null) => {
    nowPlayingTitle.value = title
  }

  const useScreenTitle = (screen: ScreenName, getTitle: ScreenTitleGetter) => {
    screenTitleGetters.set(screen, getTitle)
    onScopeDispose(() => screenTitleGetters.delete(screen))
  }

  const syncDocumentTitle = () => {
    const { getCurrentRoute } = useRouter()

    const pageTitle = computed(() => {
      if (nowPlayingTitle.value) {
        return `${nowPlayingTitle.value} ♫ ${brandName}`
      }

      const route = getCurrentRoute()
      const screenTitle = screenTitleGetters.get(route.screen)?.() || route.title

      return screenTitle ? `${screenTitle} – ${brandName}` : brandName
    })

    watchEffect(() => {
      document.title = pageTitle.value
    })
  }

  return {
    nowPlayingTitle: readonly(nowPlayingTitle),
    setNowPlayingTitle,
    useScreenTitle,
    syncDocumentTitle,
  }
}
