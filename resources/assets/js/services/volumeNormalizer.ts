import { commonStore } from '@/stores/commonStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { isSong } from '@/utils/typeGuards'
import { audioService } from '@/services/audioService'

const TARGET_LOUDNESS_LUFS = -14
const MAX_TRUE_PEAK_DBTP = -1

export const volumeNormalizer = {
  computeGainDb(streamable: Streamable | null | undefined) {
    if (
      !streamable ||
      !isSong(streamable) ||
      !commonStore.state.koel_plus.active ||
      !preferenceStore.normalize_volume ||
      streamable.loudness == null ||
      streamable.true_peak == null
    ) {
      return 0
    }

    const gainDb = TARGET_LOUDNESS_LUFS - streamable.loudness

    if (gainDb <= 0) {
      return gainDb
    }

    const headroomDb = Math.max(0, MAX_TRUE_PEAK_DBTP - streamable.true_peak)

    return Math.min(gainDb, headroomDb)
  },

  applyToStreamable(streamable: Streamable | null | undefined) {
    if (!audioService.context) {
      return
    }

    audioService.changeNormalizationGain(this.computeGainDb(streamable))
  },
}
