import { RouterKey } from '@/symbols'
import { requireInjection } from '@/utils'

export const useRouter = () => {
  const router = requireInjection(RouterKey)

  const getRouteParam = (name: string) => router.$currentRoute.value?.params?.[name]
  const getCurrentScreen = () => router.$currentRoute.value?.screen
  const isCurrentScreen = (...screens: ScreenName[]) => screens.includes(router.$currentRoute.value?.screen!)

  const onScreenActivated = (screen: ScreenName, cb: Closure) => {
    router.onRouteChanged(route => route.screen === screen && cb())
  }

  return {
    getRouteParam,
    getCurrentScreen,
    isCurrentScreen,
    onScreenActivated,
    go: router.go.bind(router),
    onRouteChanged: router.onRouteChanged.bind(router),
    resolveRoute: router.resolve.bind(router),
    triggerNotFound: router.triggerNotFound.bind(router),
  }
}
