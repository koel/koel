import { watchDebounced } from '@vueuse/core'
import type { Ref } from 'vue'
import { computed, provide, ref, watch } from 'vue'
import { FilterKeywordsKey } from '@/config/symbols'
import { useErrorHandler } from '@/composables/useErrorHandler'

export const useServerListFilter = <T>(search: (keywords: string) => Promise<T[]>) => {
  const keywords = ref('')
  const results = ref([]) as Ref<T[]>
  const searching = ref(false)
  const filtering = computed(() => keywords.value.trim().length > 0)

  let latestSearch = 0

  provide(FilterKeywordsKey, keywords)

  watch(keywords, value => {
    searching.value = value.trim().length > 0
  })

  watchDebounced(
    keywords,
    async value => {
      const trimmed = value.trim()
      const thisSearch = ++latestSearch

      if (!trimmed) {
        results.value = []
        searching.value = false

        return
      }

      searching.value = true

      try {
        const found = await search(trimmed)

        if (thisSearch === latestSearch) {
          results.value = found
        }
      } catch (error: unknown) {
        useErrorHandler('toast').handleHttpError(error)
      } finally {
        if (thisSearch === latestSearch) {
          searching.value = false
        }
      }
    },
    { debounce: 300 },
  )

  return { keywords, filtering, results, searching }
}
