import { getCurrentScope, onScopeDispose } from 'vue'
import { RouterKey } from '@/config/symbols'
import type { RouteChangedHandler } from '@/router'
import Router from '@/router'
import { requireInjection } from '@/utils/helpers'

let router: Router

export const useRouter = () => {
  router = router || requireInjection(RouterKey)

  const getRouteParam = <T = string>(name: string) => router.$currentRoute.value?.params?.[name] as T
  const getCurrentRoute = () => router.$currentRoute.value
  const getCurrentScreen = () => router.$currentRoute.value?.screen
  const isCurrentScreen = (...screens: ScreenName[]) => screens.includes(router.$currentRoute.value?.screen)

  const onRouteChanged = (handler: RouteChangedHandler) => {
    const stopListening = router.onRouteChanged(handler)

    getCurrentScope() && onScopeDispose(stopListening)

    return stopListening
  }

  const onScreenActivated = (screen: ScreenName, cb: Closure) => {
    isCurrentScreen(screen) && cb()
    onRouteChanged(route => route.screen === screen && cb())
  }

  return {
    getRouteParam,
    getCurrentRoute,
    getCurrentScreen,
    isCurrentScreen,
    onScreenActivated,
    go: Router.go,
    onRouteChanged,
    resolveRoute: router.resolve.bind(router),
    triggerNotFound: router.triggerNotFound.bind(router),
    url: Router.url,
  }
}
