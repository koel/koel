import { watchDebounced } from '@vueuse/core'
import type { Ref, WatchSource } from 'vue'
import { computed, provide, ref, watch } from 'vue'
import { FilterKeywordsKey } from '@/config/symbols'
import { useErrorHandler } from '@/composables/useErrorHandler'

export const useServerListSearch = <T>(search: (keywords: string) => Promise<T[]>, searchAgainOn?: WatchSource) => {
  const keywords = ref('')
  const results = ref([]) as Ref<T[]>
  const searching = ref(false)
  const filtering = computed(() => keywords.value.trim().length > 0)
  const { handleHttpError } = useErrorHandler('toast')

  let latestSearch = 0

  provide(FilterKeywordsKey, keywords)

  const runSearch = async () => {
    const trimmed = keywords.value.trim()
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
      handleHttpError(error)
    } finally {
      if (thisSearch === latestSearch) {
        searching.value = false
      }
    }
  }

  watch(keywords, value => {
    latestSearch++
    searching.value = value.trim().length > 0
  })

  watchDebounced(keywords, runSearch, { debounce: 300 })

  if (searchAgainOn) {
    watch(searchAgainOn, runSearch)
  }

  return { keywords, filtering, results, searching }
}
