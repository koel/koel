import { render as baseRender, RenderOptions } from '@testing-library/vue'
import { clickaway, focus, droppable } from '@/directives'

export const render = (component: any, options: RenderOptions = {}) => {
  return baseRender(component, Object.assign({
    global: {
      directives: {
        'koel-clickaway': clickaway,
        'koel-focus': focus,
        'koel-droppable': droppable
      }
    }
  }, options))
}
