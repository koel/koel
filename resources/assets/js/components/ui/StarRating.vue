<template>
  <div
    role="radiogroup"
    :aria-label="`Rating: ${currentRating} of 5 stars`"
    class="inline-flex items-center gap-0.5"
    @mouseleave="hover = 0"
  >
    <label
      v-for="star in 5"
      :key="star"
      v-koel-tooltip
      :title="titleFor(star)"
      class="cursor-pointer transition-[color] duration-150"
      :class="(hover || currentRating) >= star ? 'text-k-fg-70' : 'text-k-fg-40'"
      @click="onClick($event, star)"
      @mouseenter="hover = star"
    >
      <input
        type="radio"
        :name="groupName"
        :value="star"
        :checked="currentRating === star"
        class="sr-only"
        @change="onChange(star)"
      />
      <Icon :icon="(hover || currentRating) >= star ? faStar : faEmptyStar" :size="size" />
      <span class="sr-only">Rate {{ star }} of 5</span>
    </label>
  </div>
</template>

<script lang="ts" setup>
import { faStar } from '@fortawesome/free-solid-svg-icons'
import { faStar as faEmptyStar } from '@fortawesome/free-regular-svg-icons'
import { computed, ref, useId } from 'vue'
import { useRate, type Rateable } from '@/composables/useRate'

const props = withDefaults(
  defineProps<{
    rateable?: Rateable
    rating?: number
    size?: 'xs' | 'sm'
  }>(),
  { size: 'sm' },
)

const emit = defineEmits<{ (e: 'rate', value: number): void }>()

const { rate } = useRate()

const hover = ref(0)
const groupName = `rating-${useId()}`

const currentRating = computed(() => props.rateable?.rating ?? props.rating ?? 0)

const titleFor = (star: number) =>
  currentRating.value === star ? 'Remove rating' : `${star} star${star === 1 ? '' : 's'}`

const dispatch = (value: number) => {
  if (props.rateable) {
    rate(props.rateable, value)
  }
  emit('rate', value)
}

const onChange = (value: number) => dispatch(value)

const onClick = (event: MouseEvent, star: number) => {
  // A radio can't deselect itself on a normal click, so intercept clicks on the
  // currently-active star and dispatch 0 (clear) instead of letting the input fire change.
  if (currentRating.value === star) {
    event.preventDefault()
    dispatch(0)
  }
}
</script>
