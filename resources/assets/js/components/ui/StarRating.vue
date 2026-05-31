<template>
  <div
    role="radiogroup"
    :aria-label="`Rating: ${rating} of 5 stars`"
    class="inline-flex items-center gap-0.5"
    @mouseleave="hover = 0"
  >
    <label
      v-for="star in 5"
      :key="star"
      :title="`${star} star${star === 1 ? '' : 's'}`"
      class="cursor-pointer text-k-fg-40 hover:text-k-fg transition-[color] duration-150"
      :class="(hover || rating) >= star && 'text-k-fg-70 hover:text-k-fg'"
      @click="onClick($event, star)"
      @mouseenter="hover = star"
    >
      <input
        type="radio"
        :name="groupName"
        :value="star"
        :checked="rating === star"
        class="sr-only"
        @change="emit('rate', star)"
      />
      <Icon :icon="(hover || rating) >= star ? faStar : faEmptyStar" :size="size" />
      <span class="sr-only">Rate {{ star }} of 5</span>
    </label>
  </div>
</template>

<script lang="ts" setup>
import { faStar } from '@fortawesome/free-solid-svg-icons'
import { faStar as faEmptyStar } from '@fortawesome/free-regular-svg-icons'
import { ref, useId } from 'vue'

const props = withDefaults(
  defineProps<{
    rating: number
    size?: 'xs' | 'sm' | 'lg'
  }>(),
  { size: 'sm' },
)

const emit = defineEmits<{ (e: 'rate', value: number): void }>()

const hover = ref(0)
const groupName = `rating-${useId()}`

const onClick = (event: MouseEvent, star: number) => {
  // A radio can't deselect itself on a normal click, so intercept clicks on the
  // currently-active star and emit 0 (clear) instead of letting the input fire change.
  if (props.rating === star) {
    event.preventDefault()
    emit('rate', 0)
  }
}
</script>
