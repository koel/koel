import * as THREE from 'three'

export class ThreeSharedRenderer {
  public readonly camera: THREE.PerspectiveCamera
  public renderer!: THREE.WebGLRenderer
  public container: HTMLElement
  private timer: number
  private readonly resizeHandler: () => void

  constructor (container: HTMLElement) {
    this.container = container

    this.camera = new THREE.PerspectiveCamera(45, this.container.clientWidth / this.container.clientHeight, .1, 100)
    this.camera.position.z = 5
    this.camera.updateProjectionMatrix()

    this.timer = 0

    this.initRenderer()

    this.resizeHandler = this.resize.bind(this)
    window.addEventListener('resize', this.resizeHandler, false)
  }

  resize () {
    this.camera.aspect = this.container.clientWidth / this.container.clientHeight
    this.camera.updateProjectionMatrix()

    this.renderer.setPixelRatio(window.devicePixelRatio)
    this.renderer.setSize(this.container.clientWidth, this.container.clientHeight)
  }

  initRenderer () {
    this.renderer = new THREE.WebGLRenderer()

    this.renderer.setPixelRatio(window.devicePixelRatio)
    this.renderer.setSize(this.container.clientWidth, this.container.clientHeight)

    this.renderer.autoClear = true
    this.renderer.shadowMap.enabled = true
    this.renderer.shadowMap.type = THREE.PCFShadowMap

    this.container.appendChild(this.renderer.domElement)
  }

  render (queue: Closure[]) {
    for (let i = 0; i < queue.length; i++) {
      this.renderer.clearDepth()
      queue[i]()
    }

    this.timer += .001

    if (this.timer > 999999.) {
      this.timer = 0.
    }
  }

  ziggleCam (frame: number) {
    const e = frame
    const nLoc = new THREE.Vector3(
      Math.sin(e),
      Math.cos(e * .9) * Math.sin(e * .7),
      Math.cos(e)).normalize()

    nLoc.multiplyScalar(8. + 2. * Math.sin(2. * e))

    this.camera.position.copy(nLoc)
    this.camera.lookAt(0., 0., 0.)
    this.camera.updateProjectionMatrix()
  }

  getInverseMatrix () {
    return this.camera.matrixWorldInverse
  }

  getTimer () {
    return this.timer == undefined ? 0. : this.timer
  }

  getCamera () {
    return this.camera
  }

  destroy () {
    window.removeEventListener('resize', this.resizeHandler, false)
    this.renderer.dispose()
  }
}
