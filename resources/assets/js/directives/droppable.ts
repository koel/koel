import { Directive } from 'vue'
import { $ } from '@/utils'

export const droppable: Directive = {
  created: (el: HTMLElement, binding) => {
    el.addEventListener('dragenter', (event: DragEvent) => {
      event.preventDefault()
      $.addClass(el, 'droppable')
      event.dataTransfer!.dropEffect = 'move'

      return false
    })

    el.addEventListener('dragover', (event: DragEvent) => event.preventDefault())
    el.addEventListener('dragleave', () => $.removeClass(el, 'droppable'))

    el.addEventListener('drop', (event: DragEvent) => {
      event.preventDefault()
      event.stopPropagation()
      $.removeClass(el, 'droppable')
      binding.value(event)
    })
  }
}
