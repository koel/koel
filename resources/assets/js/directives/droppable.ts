import { $ } from '@/utils'
import { Directive } from 'vue'

export const droppable: Directive = {
  created: (el: HTMLElement, { value }: { value: TAnyFunction | never }) => {
    if (!(value instanceof Function)) {
      throw new Error(`Expect a function, received ${typeof value}`)
    }

    el.addEventListener('dragenter', (event: DragEvent) => {
      event.preventDefault()
      $.addClass(el, 'droppable')
      event.dataTransfer!.dropEffect = 'move'

      return false
    })

    el.addEventListener('dragover', (event: DragEvent): void => event.preventDefault())

    el.addEventListener('dragleave', () => $.removeClass(el, 'droppable'))

    el.addEventListener('drop', (event: DragEvent) => {
      event.preventDefault()
      event.stopPropagation()
      $.removeClass(el, 'droppable')
      value(event)
    })
  }
}
