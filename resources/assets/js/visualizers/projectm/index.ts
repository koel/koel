import projectMScriptUrl from '@koel-project/projectm-wasm/koel_projectm.js?url'
import projectMWasmUrl from '@koel-project/projectm-wasm/koel_projectm.wasm?url'
import { sample } from 'lodash-es'
import { watch } from 'vue'
import { milkdropPresetLibrary } from '@/services/milkdropPresetLibrary'
import { milkdropPresetStore, RANDOM_PRESET } from '@/stores/milkdropPresetStore'
import { configureAnalyser } from '@/visualizers/analyser'
import { startFrameLoop } from '@/visualizers/frameLoop'
import { getRenderPixelRatio } from '@/visualizers/pixelRatio'

interface ProjectMModule {
  cwrap: (name: string, returnType: string | null, argumentTypes: string[]) => (...args: any[]) => any
  _malloc: (size: number) => number
  _free: (pointer: number) => void
  HEAPF32: Float32Array
}

declare global {
  interface Window {
    KoelProjectM?: (options: { locateFile: (file: string) => string }) => Promise<ProjectMModule>
  }
}

const CANVAS_ID = 'projectm-canvas'
const PRESET_DURATION_MS = 30_000
const SAMPLES_PER_FRAME = 2048

let scriptLoaded: Promise<void> | null = null

const loadProjectMScript = () =>
  (scriptLoaded ??= new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = projectMScriptUrl
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Failed to load projectM'))
    document.head.appendChild(script)
  }))

export const init = async (container: HTMLElement) => {
  await loadProjectMScript()

  const projectM = await window.KoelProjectM!({ locateFile: () => projectMWasmUrl })

  const startProjectM = projectM.cwrap('koel_projectm_init', 'number', ['string', 'number', 'number'])
  const loadPreset = projectM.cwrap('koel_projectm_load_preset', null, ['string'])
  const addPcm = projectM.cwrap('koel_projectm_add_pcm', null, ['number', 'number'])
  const renderFrame = projectM.cwrap('koel_projectm_render', null, [])
  const resize = projectM.cwrap('koel_projectm_resize', null, ['number', 'number'])
  const destroy = projectM.cwrap('koel_projectm_destroy', null, [])

  const canvas = container.appendChild(document.createElement('canvas'))
  canvas.id = CANVAS_ID
  canvas.style.cssText = 'display: block; width: 100%; height: 100%'

  const hint = container.appendChild(document.createElement('p'))
  hint.className = 'absolute inset-0 grid place-items-center text-k-fg-50 pointer-events-none'
  hint.textContent = 'Import MilkDrop presets (.milk) to start'

  const sizeCanvas = () => {
    canvas.width = Math.round(container.clientWidth * getRenderPixelRatio())
    canvas.height = Math.round(container.clientHeight * getRenderPixelRatio())
  }

  sizeCanvas()

  if (startProjectM(`#${CANVAS_ID}`, canvas.width, canvas.height) !== 0) {
    throw new Error('Failed to start projectM')
  }

  const { state } = milkdropPresetStore
  let playingName: string | null = null
  let presetTimer = 0

  const playPreset = async (name: string) => {
    const content = await milkdropPresetLibrary.getContent(name)

    if (content) {
      loadPreset(content)
      playingName = name
    }
  }

  const playRandomPreset = () => {
    const otherNames = state.names.filter(name => name !== playingName)
    const name = sample(otherNames.length ? otherNames : state.names)

    name && playPreset(name)
  }

  const applySelection = () => {
    window.clearInterval(presetTimer)
    hint.hidden = state.names.length > 0

    if (state.selected !== RANDOM_PRESET && state.names.includes(state.selected)) {
      playPreset(state.selected)
      return
    }

    playRandomPreset()
    presetTimer = window.setInterval(playRandomPreset, PRESET_DURATION_MS)
  }

  await milkdropPresetStore.load()
  applySelection()

  const stopWatchingSelection = watch(() => state.selected, applySelection)

  const stopWatchingNames = watch(
    () => state.names,
    names => {
      hint.hidden = names.length > 0

      if (!playingName && names.length) {
        applySelection()
      }
    },
  )

  const analyser = configureAnalyser({ fftSize: SAMPLES_PER_FRAME })
  const samples = new Float32Array(SAMPLES_PER_FRAME)
  const samplesPointer = projectM._malloc(samples.byteLength)

  const stopFrameLoop = startFrameLoop(() => {
    analyser.getFloatTimeDomainData(samples)
    projectM.HEAPF32.set(samples, samplesPointer / samples.BYTES_PER_ELEMENT)
    addPcm(samplesPointer, samples.length)
    renderFrame()
  })

  const resizeObserver = new ResizeObserver(() => {
    sizeCanvas()
    resize(canvas.width, canvas.height)
  })

  resizeObserver.observe(container)

  return () => {
    stopFrameLoop()
    window.clearInterval(presetTimer)
    resizeObserver.disconnect()
    stopWatchingSelection()
    stopWatchingNames()
    destroy()
    canvas.getContext('webgl2')?.getExtension('WEBGL_lose_context')?.loseContext()
    projectM._free(samplesPointer)
    canvas.remove()
    hint.remove()
  }
}
