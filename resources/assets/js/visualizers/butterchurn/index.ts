import butterchurn from 'butterchurn'
import butterchurnPresets from 'butterchurn-presets'
import { audioService } from '@/services/audioService'

export const initVisualizer = (container: HTMLElement) => {
  const audioContext = audioService.context

  const canvas = document.createElement('canvas')
  canvas.width = container.clientWidth
  canvas.height = container.clientHeight
  container.appendChild(canvas)

  const visualizer = butterchurn.createVisualizer(audioContext, canvas, {
    width: canvas.width,
    height: canvas.height,
  })

  const audioNode = audioService.source
  visualizer.connectAudio(audioNode)

  const presets = butterchurnPresets.getPresets()
  const presetKeys = Object.keys(presets)

  const loadRandomPreset = () => {
    const randomKey = presetKeys[Math.floor(Math.random() * presetKeys.length)]
    visualizer.loadPreset(presets[randomKey], 0.0)
  }

  let currentPresetIndex = 0

  const cyclePreset = () => {
    const presetKey = presetKeys[currentPresetIndex]
    visualizer.loadPreset(presets[presetKey], 2.0)
    currentPresetIndex = (Math.floor(Math.random() * presetKeys.length)) % presetKeys.length // Loop back to the start
  }

  loadRandomPreset()

  const presetInterval = setInterval(cyclePreset, 30000)

  visualizer.setRendererSize(canvas.width, canvas.height)

  const render = () => {
    visualizer.render()
    requestAnimationFrame(render)
  }

  render()

  const handleResize = () => {
    canvas.width = container.clientWidth
    canvas.height = container.clientHeight
    visualizer.setRendererSize(canvas.width, canvas.height)
  }

  window.addEventListener('resize', handleResize)

  return () => {
    window.removeEventListener('resize', handleResize)

    if (canvas.parentNode) {
      canvas.parentNode.removeChild(canvas)
    }

    clearInterval(presetInterval)
  }
}
