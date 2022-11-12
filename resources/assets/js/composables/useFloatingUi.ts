import { isRef, Ref } from 'vue'
import { arrow as arrowMiddleware, autoUpdate, computePosition, flip, offset, Placement } from '@floating-ui/dom'

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

    let arrow: HTMLElement | null = null

    if (mergedConfig.useArrow) {
      arrow = document.createElement('div')
      arrow.className = 'arrow'
      floating.appendChild(arrow)
    }

    const middleware = [
      flip(),
      offset(6)
    ]

    if (arrow) {
      middleware.push(arrowMiddleware({
        element: arrow,
        padding: 6
      }))
    }

    const update = async () => {
      const { x, y, placement: _, middlewareData } = await computePosition(reference, floating, {
        placement: mergedConfig.placement,
        middleware
      })

      floating.style.left = `${x}px`
      floating.style.top = `${y}px`

      if (arrow) {
        const { x: arrowX, y: arrowY } = middlewareData.arrow

        const staticSide = {
          top: 'bottom',
          right: 'left',
          bottom: 'top',
          left: 'right'
        }[mergedConfig.placement.split('-')[0]]

        Object.assign(arrow.style, {
          left: arrowX != null ? `${arrowX}px` : '',
          top: arrowY != null ? `${arrowY}px` : '',
          right: '',
          bottom: '',
          [staticSide]: '-4px'
        })
      }
    }

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
