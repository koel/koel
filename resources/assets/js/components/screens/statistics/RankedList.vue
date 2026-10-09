<template>
  <section class="flex flex-col gap-3">
    <h3 class="text-k-fg">{{ title }}</h3>
    <ol class="flex flex-col gap-2">
      <li v-for="(item, i) in items" :key="item.key" class="flex items-center gap-3">
        <span class="w-5 text-right text-k-fg-50 tabular-nums">{{ i + 1 }}</span>
        <img v-if="item.image" :src="item.image" alt="" class="w-9 h-9 rounded-sm object-cover" />
        <div class="flex-1 min-w-0">
          <a :href="item.href" class="block truncate text-k-fg hover:text-k-highlight">{{ item.title }}</a>
          <p v-if="item.subtitle" class="truncate text-sm text-k-fg-50">{{ item.subtitle }}</p>
        </div>
        <span class="text-sm text-k-fg-70 tabular-nums whitespace-nowrap">{{ pluralize(item.plays, 'play') }}</span>
      </li>
    </ol>
  </section>
</template>

<script lang="ts" setup>
import { pluralize } from '@/utils/formatters'

export interface RankedItem {
  key: string
  title: string
  subtitle?: string
  image?: string
  href: string
  plays: number
}

defineProps<{ title: string; items: RankedItem[] }>()
</script>
