import { useRouter } from '@/composables'

export const useScreen = (screen: ScreenName) => {
  const { onRouteChanged } = useRouter()
  const onScreenActivated = (cb: Closure) => onRouteChanged(route => route.screen === screen && cb())

  return {
    onScreenActivated
  }
}
