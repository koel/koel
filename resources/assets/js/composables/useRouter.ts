import { RouterKey } from '@/symbols'
import Router from '@/router'
import { requireInjection } from '@/utils/helpers'

let router: Router

export const useRouter = () => {
  router = router || requireInjection(RouterKey)

  const getRouteParam = (name: string) => router.$currentRoute.value?.params?.[name]
  const getCurrentScreen = () => router.$currentRoute.value?.screen
  const isCurrentScreen = (...screens: ScreenName[]) => screens.includes(router.$currentRoute.value?.screen)

  const onScreenActivated = (screen: ScreenName, cb: Closure) => {
    isCurrentScreen(screen) && cb()
    router.onRouteChanged(route => route.screen === screen && cb())
  }

  return {
    getRouteParam,
    getCurrentScreen,
    isCurrentScreen,
    onScreenActivated,
    go: Router.go,
    onRouteChanged: router.onRouteChanged.bind(router),
    resolveRoute: router.resolve.bind(router),
    triggerNotFound: router.triggerNotFound.bind(router),
    url: Router.url,
  }
}
