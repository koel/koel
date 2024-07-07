import { isRef, Ref } from 'vue'
import { arrow as arrowMiddleware, autoUpdate, flip, offset, Placement } from '@floating-ui/dom'
import { updateFloatingUi } from '@/utils'

export type Config = {
  placement: Placement,
  useArrow: boolean,
  autoTrigger: boolean,
}

export const useFloatingUi = (
  reference: HTMLElement | Ref<HTMLElement | undefined>,
  floating: HTMLElement | Ref<HTMLElement | undefined>,
  config: Partial<Config> = {}
) => {
  const extractRef = <T extends HTMLElement | Ref<HTMLElement | undefined>> (ref: T): HTMLElement => {
    if (isRef(ref) && !ref.value) {
      throw new TypeError('Reference element is not defined')
    }

    return isRef(ref) ? ref.value! : ref
  }

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
    const referenceElement = extractRef(reference)
    const floatingElement = extractRef(floating)

    floatingElement.style.display = 'none'

    const middleware = [
      flip(),
      offset(6)
    ]

    let arrow: HTMLElement

    if (mergedConfig.useArrow) {
      arrow = document.createElement('div')
      arrow.className = 'arrow'
      floatingElement.appendChild(arrow)

      middleware.push(arrowMiddleware({
        element: arrow,
        padding: 6
      }))
    }

    const update = async () => await updateFloatingUi(referenceElement, floatingElement, {
      placement: mergedConfig.placement,
      middleware
    }, arrow)

    _cleanUp = autoUpdate(referenceElement, floatingElement, update)

    _show = async () => {
      floatingElement.style.display = 'block'
      await update()
    }

    _hide = () => (floatingElement.style.display = 'none')
    _trigger = () => floatingElement.style.display === 'none' ? _show() : _hide()

    if (mergedConfig.autoTrigger) {
      referenceElement.addEventListener('mouseenter', _show)
      referenceElement.addEventListener('focus', _show)
      referenceElement.addEventListener('mouseleave', _hide)
      referenceElement.addEventListener('blur', _hide)
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
