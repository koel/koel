<template>
  <div class="inline-flex flex-wrap gap-1 rounded-full bg-k-fg-5 p-1" role="radiogroup">
    <label
      v-for="option in options"
      :key="option.value"
      :data-testid="option.testId"
      class="flex items-center gap-2 rounded-full px-4 py-1.5 cursor-pointer text-k-fg-70 hover:text-k-fg has-checked:bg-k-highlight has-checked:text-k-highlight-fg has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-k-highlight"
    >
      <input v-model="value" :name :value="option.value" class="sr-only" type="radio" />
      <slot :option>{{ option.label }}</slot>
    </label>
  </div>
</template>

<script lang="ts" setup generic="T extends string">
defineProps<{
  name: string
  options: { value: T; label: string; testId?: string }[]
}>()

const value = defineModel<T>({ required: true })
</script>
