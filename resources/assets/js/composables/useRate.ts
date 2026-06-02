import type { Reactive } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'

export type Rateable = Song | Album | Artist

export const useRate = () => {
  const rate = async (entity: Rateable, value: number) => {
    if (entity.type === 'songs') {
      await playableStore.rate(entity as Reactive<Song>, value)
    } else if (entity.type === 'albums') {
      await albumStore.rate(entity as Reactive<Album>, value)
    } else if (entity.type === 'artists') {
      await artistStore.rate(entity as Reactive<Artist>, value)
    }
  }

  return { rate }
}
