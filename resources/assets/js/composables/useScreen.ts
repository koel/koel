import { ref, watch } from 'vue'
import { requireInjection } from '@/utils'
import { ActiveScreenKey } from '@/symbols'

export const useScreen = (currentScreen: ScreenName) => {
  const activeScreen = requireInjection(ActiveScreenKey, ref('Home'))

  const onScreenActivated = (cb: Closure) => watch(activeScreen, screen => screen === currentScreen && cb(), {
    immediate: true
  })

  return {
    activeScreen,
    onScreenActivated
  }
}
