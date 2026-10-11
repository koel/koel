import { sample } from 'lodash-es'
import { audioService } from '@/services/audioService'
import { milkdropPresetLibrary } from '@/services/milkdropPresetLibrary'
import { commonStore } from '@/stores/commonStore'
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

const getAssetUrl = (file: string) => `${commonStore.state.cdn_url}visualizer/projectm/${file}`

let scriptLoaded: Promise<void> | null = null

const loadProjectMScript = () =>
  (scriptLoaded ??= new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = getAssetUrl('koel_projectm.js')
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Failed to load projectM'))
    document.head.appendChild(script)
  }))

export const init = async (container: HTMLElement) => {
  await loadProjectMScript()

  const projectM = await window.KoelProjectM!({ locateFile: getAssetUrl })

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
  hint.textContent = 'Drop MilkDrop presets (.milk) here'

  const sizeCanvas = () => {
    canvas.width = Math.round(container.clientWidth * getRenderPixelRatio())
    canvas.height = Math.round(container.clientHeight * getRenderPixelRatio())
  }

  sizeCanvas()

  if (startProjectM(`#${CANVAS_ID}`, canvas.width, canvas.height) !== 0) {
    throw new Error('Failed to start projectM')
  }

  let presets = await milkdropPresetLibrary.getAll()

  const loadRandomPreset = () => {
    hint.hidden = presets.length > 0
    presets.length && loadPreset(sample(presets)!.content)
  }

  loadRandomPreset()
  const presetTimer = window.setInterval(loadRandomPreset, PRESET_DURATION_MS)

  const allowDrop = (event: DragEvent) => event.preventDefault()

  const importDroppedPresets = async (event: DragEvent) => {
    event.preventDefault()

    const imported = await milkdropPresetLibrary.importFiles(event.dataTransfer?.files ?? [])

    if (!imported.length) {
      return
    }

    presets = await milkdropPresetLibrary.getAll()
    hint.hidden = true
    loadPreset(imported[0].content)
  }

  container.addEventListener('dragover', allowDrop)
  container.addEventListener('drop', importDroppedPresets)

  const analyser = audioService.analyzer
  analyser.fftSize = SAMPLES_PER_FRAME
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
    container.removeEventListener('dragover', allowDrop)
    container.removeEventListener('drop', importDroppedPresets)
    destroy()
    projectM._free(samplesPointer)
    canvas.remove()
    hint.remove()
  }
}
