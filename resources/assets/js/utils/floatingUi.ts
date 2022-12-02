import { computePosition, ComputePositionConfig } from '@floating-ui/dom'

export const updateFloatingUi = async (
  reference: HTMLElement,
  floating: HTMLElement,
  options: Partial<ComputePositionConfig>,
  arrow?: HTMLElement
) => {
  options.placement = options.placement || 'bottom'
  const { x, y, middlewareData } = await computePosition(reference, floating, options)

  floating.style.left = `${x}px`
  floating.style.top = `${y}px`

  if (arrow) {
    const { x: arrowX, y: arrowY } = middlewareData.arrow!

    const staticSide = {
      top: 'bottom',
      right: 'left',
      bottom: 'top',
      left: 'right'
    }[options.placement.split('-')[0]]

    Object.assign(arrow.style, {
      left: arrowX != null ? `${arrowX}px` : '',
      top: arrowY != null ? `${arrowY}px` : '',
      right: '',
      bottom: '',
      [staticSide!]: '-4px'
    })
  }
}
