import { $ } from '@/utils'
import { Directive } from 'vue'

export const droppable: Directive = {
  created: (el: HTMLElement, { value }: { value: Function | never }): void => {
    if (!(value instanceof Function)) {
      throw new Error(`Expect a function, received ${typeof value}`)
    }

    el.addEventListener('dragenter', (event: DragEvent): boolean => {
      event.preventDefault()
      $.addClass(el, 'droppable')
      event.dataTransfer!.dropEffect = 'move'

      return false
    })

    el.addEventListener('dragover', (event: DragEvent): void => event.preventDefault())

    el.addEventListener('dragleave', (): void => $.removeClass(el, 'droppable'))

    el.addEventListener('drop', (event: DragEvent): void => {
      event.preventDefault()
      event.stopPropagation()
      $.removeClass(el, 'droppable')
      value(event)
    })
  }
}
