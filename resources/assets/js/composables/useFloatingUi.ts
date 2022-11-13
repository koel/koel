import { isRef, Ref } from 'vue'
import { arrow as arrowMiddleware, autoUpdate, flip, offset, Placement } from '@floating-ui/dom'
import { updateFloatingUi } from '@/utils'

export type Config = {
  placement: Placement,
  useArrow: boolean,
  autoTrigger: boolean,
}

export const useFloatingUi = (
  reference: HTMLElement | Ref<HTMLElement>,
  floating: HTMLElement | Ref<HTMLElement>,
  config: Partial<Config> = {}
) => {
  const mergedConfig: Config = Object.assign({
    placement: 'bottom',
    useArrow: true,
    autoTrigger: true
  }, config)

  let _cleanUp: Closure
  let _show: Closure
  let _hide: Closure
  let _trigger: Closure

  const setup = () => {
    reference = isRef(reference) ? reference.value : reference
    floating = isRef(floating) ? floating.value : floating

    floating.style.display = 'none'

    const middleware = [
      flip(),
      offset(6)
    ]

    let arrow: HTMLElement

    if (mergedConfig.useArrow) {
      arrow = document.createElement('div')
      arrow.className = 'arrow'
      floating.appendChild(arrow)

      middleware.push(arrowMiddleware({
        element: arrow,
        padding: 6
      }))
    }

    const update = async () => await updateFloatingUi(reference, floating, {
      placement: mergedConfig.placement,
      middleware
    }, arrow)

    _cleanUp = autoUpdate(reference, floating, update)

    _show = async () => {
      floating.style.display = 'block'
      await update()
    }

    _hide = () => (floating.style.display = 'none')
    _trigger = () => floating.style.display === 'none' ? _show() : _hide()

    if (mergedConfig.autoTrigger) {
      reference.addEventListener('mouseenter', _show)
      reference.addEventListener('focus', _show)
      reference.addEventListener('mouseleave', _hide)
      reference.addEventListener('blur', _hide)
    }
  }

  return {
    setup,
    teardown: () => _cleanUp(),
    show: () => _show(),
    hide: () => _hide(),
    trigger: () => _trigger()
  }
}
