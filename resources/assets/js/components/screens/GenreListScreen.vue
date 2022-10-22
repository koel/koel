<template>
  <section id="genresWrapper">
    <ScreenHeader layout="compact">
      Genres
    </ScreenHeader>
    <div class="main-scroll-wrap">
      <ul class="genres" v-if="genres">
        <li v-for="genre in genres" :key="genre.name" :class="`level-${getLevel(genre)}`">
          <a :href="`/#/genres/${genre.name}`" :title="`${genre.name}: ${pluralize(genre.song_count, 'song')}`">
            <span class="name">{{ genre.name }}</span>
            <span class="count">{{ genre.song_count }}</span>
          </a>
        </li>
      </ul>
      <ul class="genres" v-else>
        <li v-for="i in 20" :key="i">
          <GenreItemSkeleton/>
        </li>
      </ul>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { maxBy, minBy } from 'lodash'
import { computed, onMounted, ref } from 'vue'
import { genreStore } from '@/stores'
import { pluralize } from '@/utils'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import GenreItemSkeleton from '@/components/ui/skeletons/GenreItemSkeleton.vue'

const genres = ref<Genre[]>()

const mostPopular = computed(() => maxBy(genres.value, 'song_count'))
const leastPopular = computed(() => minBy(genres.value, 'song_count'))

const levels = computed(() => {
  const max = mostPopular.value?.song_count || 1
  const min = leastPopular.value?.song_count || 1
  const range = max - min
  const step = range / 5

  return [min, min + step, min + step * 2, min + step * 3, min + step * 4, max]
})

const getLevel = (genre: Genre) => {
  const index = levels.value.findIndex((level) => genre.song_count <= level)
  return index === -1 ? 5 : index
}

onMounted(async () => genres.value = await genreStore.fetchAll())
</script>

<style lang="scss" scoped>
.genres {
  text-align: center;

  li {
    display: inline-block;
    border-radius: 999rem;
    margin: .2rem;
    font-size: var(--unit);
    transition: opacity .2s ease-in-out, transform .2s ease-in-out;
    vertical-align: middle;

    &:hover {
      transform: scale(1.1);
      opacity: 1;
    }

    a {
      background: rgba(255, 255, 255, .03);
      display: inline-flex;
      padding: calc(var(--unit) / 4) calc(var(--unit) / 4) calc(var(--unit) / 4) calc(var(--unit));
      gap: 12px;
      align-items: center;
      border-radius: 999rem;
      transition: background-color .2s ease-in-out, color .2s ease-in-out;

      &:hover {
        color: var(--color-text-primary);
        background: var(--color-highlight);
      }

      &:active {
        transform: translateX(2px) translateY(2px);
      }
    }

    .count {
      padding: calc(var(--unit) - 1rem) calc(var(--unit) / 2);
      background: rgba(255, 255, 255, .1);
      border-radius: 999rem;
      font-size: calc(var(--unit) * 2 / 3);
      box-shadow: 0 0 5px 0 rgba(0, 0, 0, .3);
      min-width: calc(var(--unit) * 1.5);
      text-align: center;
    }
  }

  @for $i from 0 through 5 {
    .level-#{$i} {
      $zoom: 1 + $i * .4;
      --unit: #{$zoom}rem;
      opacity: .8 + $i * .04;
    }
  }
}
</style>
