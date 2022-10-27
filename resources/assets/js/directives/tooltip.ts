import { arrow, autoUpdate, computePosition, offset, Placement } from '@floating-ui/dom'
import { Directive, DirectiveBinding } from 'vue'

type ElementWithTooltip = HTMLElement & {
  $tooltip?: HTMLDivElement,
  $cleanup?: Closure
}

const getOrCreateTooltip = (el: ElementWithTooltip): HTMLElement => {
  if (el.$tooltip) return el.$tooltip

  el.$tooltip = document.createElement('div')
  el.$tooltip.classList.add('tooltip')

  const arrow = document.createElement('div')
  arrow.classList.add('tooltip-arrow')

  const content = document.createElement('div')
  content.classList.add('tooltip-content')

  el.$tooltip.appendChild(content)
  el.$tooltip.appendChild(arrow)

  document.body.appendChild(el.$tooltip)

  return el.$tooltip
}

const init = (el: ElementWithTooltip, binding: DirectiveBinding) => {
  const $tooltip = getOrCreateTooltip(el)

  // make sure the actual title is removed from the element, but keep a backup for the updated() hook calls
  $tooltip.querySelector<HTMLDivElement>('.tooltip-content')!.innerText = binding.value
    || el.title
    || el.getAttribute('data-title')
    || el.innerText

  if (el.title && !el.getAttribute('data-title')) {
    el.setAttribute('data-title', el.title)
    el.removeAttribute('title')
  }

  const $arrow = $tooltip.querySelector<HTMLDivElement>('.tooltip-arrow')!

  let placement: Placement = 'bottom'

  ;(['left', 'right', 'top', 'bottom'] as Placement[]).forEach(p => {
    if (binding.modifiers[p]) {
      placement = p
    }
  })

  const update = async () => {
    const { x, y, middlewareData } = await computePosition(el, $tooltip, {
      placement,
      middleware: [
        arrow({ element: $arrow }),
        offset(8)
      ]
    })

    Object.assign($tooltip.style, {
      top: `${y}px`,
      left: `${x}px`
    })

    // @ts-ignore
    const { x: arrowX, y: arrowY } = middlewareData.arrow

    const staticSide = {
      top: 'bottom',
      right: 'left',
      bottom: 'top',
      left: 'right'
    }[placement.split('-')[0]]

    Object.assign($arrow.style, {
      left: arrowX != null ? `${arrowX}px` : '',
      top: arrowY != null ? `${arrowY}px` : '',
      right: '',
      bottom: '',
      // @ts-ignore
      [staticSide]: '-4px'
    })
  }

  el.$cleanup = el.$cleanup || autoUpdate(el, $tooltip, update)

  const showTooltip = async () => {
    $tooltip.classList.add('show')
    await update()
  }

  const hideTooltip = () => $tooltip.classList.remove('show')

  el.addEventListener('mouseenter', showTooltip)
  el.addEventListener('focus', showTooltip)
  el.addEventListener('mouseleave', hideTooltip)
  el.addEventListener('blur', hideTooltip)
}

export const tooltip: Directive = {
  mounted: init,
  updated: init,

  beforeUnmount: (el: ElementWithTooltip, binding) => {
    el.$cleanup && el.$cleanup()
    el.$tooltip && document.body.removeChild(el.$tooltip)
  }
}
