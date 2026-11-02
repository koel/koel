<template>
  <div
    class="flex flex-wrap items-center gap-1 min-h-[37px] px-1.5 py-1 rounded-sm bg-k-bg-input text-k-fg-input border border-k-fg-10 cursor-text focus-within:outline-1 focus-within:-outline-offset-1 focus-within:outline-k-highlight"
    @click="input?.focus()"
  >
    <span
      v-for="tag in tags"
      :key="tag"
      class="inline-flex items-center gap-0.5 py-0.5 pr-0.5 pl-2 rounded-sm bg-k-fg-10 leading-5 whitespace-nowrap"
      data-testid="tag"
    >
      {{ tag }}
      <button
        :aria-label="`Remove ${tag}`"
        class="grid place-items-center w-[18px] h-[18px] rounded-sm text-k-fg-50 hover:text-k-fg hover:bg-k-fg-10"
        type="button"
        @click.stop="removeTag(tag)"
      >
        <Icon :icon="faXmark" size="sm" />
      </button>
    </span>

    <input
      :id
      ref="input"
      v-model="draft"
      :aria-label="name"
      :list="suggestionListId"
      :name="name"
      :placeholder="tags.length ? '' : placeholder"
      autocomplete="off"
      class="flex-1 min-w-20 px-1.5 py-0.5 text-base bg-transparent outline-none"
      type="text"
      @blur="commitDraft"
      @input="onInput"
      @keydown.backspace="removeLastTagIfDraftIsEmpty"
      @keydown.enter="onEnter"
      @keydown.esc="onEscape"
    />

    <datalist v-if="suggestions.length" :id="suggestionListId">
      <option v-for="suggestion in availableSuggestions" :key="suggestion" :value="suggestion" />
    </datalist>
  </div>
</template>

<script lang="ts" setup>
import { faXmark } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, useId } from 'vue'

const props = withDefaults(defineProps<{ name: string; id?: string; placeholder?: string; suggestions?: string[] }>(), {
  placeholder: '',
  suggestions: () => [],
})

const tags = defineModel<string[]>({ required: true })

const input = ref<HTMLInputElement>()
const draft = ref('')

const suggestionListId = props.suggestions.length ? `tag-suggestions-${useId()}` : undefined

const hasTag = (name: string) => tags.value.some(tag => tag.toLowerCase() === name.toLowerCase())

const availableSuggestions = computed(() => props.suggestions.filter(suggestion => !hasTag(suggestion)))

const addTags = (text: string) => {
  const newTags = text
    .split(',')
    .map(name => name.trim())
    .filter(Boolean)
    .reduce<string[]>((added, name) => {
      if (!hasTag(name) && !added.some(tag => tag.toLowerCase() === name.toLowerCase())) {
        added.push(name)
      }

      return added
    }, [])

  if (newTags.length) {
    tags.value = [...tags.value, ...newTags]
  }
}

const commitDraft = () => {
  addTags(draft.value)
  draft.value = ''
}

const onEnter = (event: KeyboardEvent) => {
  if (event.isComposing) {
    return
  }

  event.preventDefault()
  commitDraft()
}

const onEscape = (event: KeyboardEvent) => {
  if (event.isComposing) {
    event.stopPropagation()
    return
  }

  commitDraft()
}

const onInput = () => {
  if (draft.value.includes(',')) {
    commitDraft()
  }
}

const removeTag = (name: string) => {
  tags.value = tags.value.filter(tag => tag !== name)
}

const removeLastTagIfDraftIsEmpty = (event: KeyboardEvent) => {
  if (!event.isComposing && !draft.value && tags.value.length) {
    tags.value = tags.value.slice(0, -1)
  }
}
</script>
