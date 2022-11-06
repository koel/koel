import { ThreeSharedRenderer } from './ThreeSharedRenderer'
import { ThreePBR } from './ThreePBR'
import { ThreePointLight } from './ThreePointLight'
import { NoiseBlob } from './NoiseBlob'
import { AudioAnalyzer } from './AudioAnalyzer'
import { DeviceChecker } from './DeviceChecker'

let analyzer: AudioAnalyzer
let renderer: ThreeSharedRenderer
let renderQueue
let blob
let pbr
let light
let deviceChecker

export const init = (container: HTMLElement) => {
  deviceChecker = new DeviceChecker()
  const isRetina = deviceChecker.isRetina()

  analyzer = new AudioAnalyzer()
  renderer = new ThreeSharedRenderer(container)

  pbr = new ThreePBR()
  light = new ThreePointLight()

  blob = new NoiseBlob(renderer, analyzer, light)
  blob.setPBR(pbr)

  isRetina && blob.setRetina()

  renderQueue = [
    blob.update.bind(blob)
  ]

  update()

  return () => {
    // this will destroy the renderers and related class instances
    blob.destroy()
  }
}

const update = () => {
  requestAnimationFrame(update)

  analyzer.update()

  // update blob
  blob.updatePBR()

  // update pbr
  pbr.exposure = 5. + 30. * analyzer.getLevel()

  light.ziggle(renderer.getTimer())
  renderer.ziggleCam(renderer.getTimer())

  renderer.render(renderQueue)
}
