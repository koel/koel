<template>
  <nav aria-label="Settings sections">
    <div class="md:hidden px-6 py-4 border-b border-k-fg-5">
      <SelectBox v-model="selectedId" aria-label="Settings section" data-testid="settings-section-picker">
        <template v-if="showsGroupNames">
          <optgroup v-for="group in groups" :key="group.name" :label="group.name">
            <option v-for="section in group.sections" :key="section.id" :value="section.id">{{ section.label }}</option>
          </optgroup>
        </template>
        <template v-else>
          <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.label }}</option>
        </template>
      </SelectBox>
    </div>

    <div aria-orientation="vertical" class="hidden md:flex flex-col w-52 h-full" role="tablist">
      <template v-for="group in groups" :key="group.name">
        <h3 v-if="showsGroupNames" class="rail-cell group-name">{{ group.name }}</h3>
        <button
          v-for="section in group.sections"
          :id="tabIdOf(section.id)"
          :key="section.id"
          :aria-controls="panelId"
          :aria-selected="section.id === selectedId"
          :data-testid="`settings-section-${section.id}`"
          class="rail-cell section"
          role="tab"
          type="button"
          @click="selectedId = section.id"
        >
          {{ section.label }}
        </button>
      </template>
      <div aria-hidden="true" class="rail-cell flex-1" />
    </div>
  </nav>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import type { SettingsSection } from '@/components/screens/SettingsScreen.vue'

import SelectBox from '@/components/ui/form/SelectBox.vue'

const props = defineProps<{
  sections: SettingsSection[]
  panelId: string
}>()

const selectedId = defineModel<string>({ required: true })

const groups = computed(() =>
  (['Account', 'Server'] as const)
    .map(name => ({ name, sections: props.sections.filter(section => section.group === name) }))
    .filter(group => group.sections.length > 0),
)

const showsGroupNames = computed(() => groups.value.length > 1)

const tabIdOf = (sectionId: string) => `settingsSection-${sectionId}`
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

/*
 * The rail is darker than the panel, but the selected section must show the panel's own background, which can be a
 * theme image. So the darkening goes on each cell instead of on the rail, and the selected cell simply leaves it out.
 */
.rail-cell {
  @apply bg-k-bg-30 border-r border-k-fg-10;
}

.group-name {
  @apply px-6 pt-4 pb-1.5 text-[0.7rem] uppercase tracking-widest text-k-fg-50 first:pt-5;
}

.section {
  @apply px-6 py-2.5 text-left text-k-fg-70 cursor-pointer border-y border-y-transparent;

  &:hover {
    @apply text-k-fg bg-k-fg-5;
  }

  &:focus-visible {
    @apply outline-2 -outline-offset-2 outline-k-highlight;
  }

  &[aria-selected='true'] {
    @apply bg-transparent border-r-transparent border-y-k-fg-10 text-k-fg first:border-t-transparent;
  }
}
</style>
