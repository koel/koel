import { RouterKey } from '@/symbols'
import { requireInjection } from '@/utils'

export const useRouter = () => {
  const router = requireInjection(RouterKey)

  return {
    go: router.go.bind(router),
    onRouteChanged: router.onRouteChanged.bind(router),
    resolveRoute: router.resolve.bind(router),
    triggerNotFound: router.triggerNotFound.bind(router),
    getRouteParam: (name: string) => router.$currentRoute.value?.params?.[name],
    getCurrentScreen: () => router.$currentRoute.value?.screen,
    isCurrentScreen: (...screens: ScreenName[]) => screens.includes(router.$currentRoute.value?.screen!)
  }
}
