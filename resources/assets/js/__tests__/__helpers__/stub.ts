import { defineComponent } from 'vue'

export const stub = (testId: string = 'stub') => {
  return defineComponent({
    template: `<br data-testid="${testId}"/>`
  })
}
