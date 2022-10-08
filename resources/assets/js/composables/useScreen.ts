import { RouterKey } from '@/symbols'
import { requireInjection } from '@/utils'

export const useScreen = (screen: ScreenName) => {
  const router = requireInjection(RouterKey)
  const onScreenActivated = (cb: Closure) => router.onRouteChanged(route => route.screen === screen && cb())

  return {
    onScreenActivated
  }
}
