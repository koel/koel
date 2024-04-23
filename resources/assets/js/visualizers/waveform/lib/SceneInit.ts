import * as THREE from 'three'

export default class SceneInit {
  public scene: THREE.Scene
  private readonly fov: number
  private container: HTMLElement
  private readonly camera: THREE.PerspectiveCamera
  private clock: THREE.Clock
  private renderer: THREE.WebGLRenderer
  private uniforms: any
  private readonly onWindowResize: () => void
  private readonly onDocumentWheel: (e: WheelEvent) => void

  constructor (container: HTMLElement, fov = 36) {
    this.container = container
    this.fov = fov

    this.camera = new THREE.PerspectiveCamera(
      this.fov,
      this.container.clientWidth / this.container.clientHeight,
      1,
      1000
    )

    this.camera.position.z = 128

    this.clock = new THREE.Clock()
    this.scene = new THREE.Scene()

    this.uniforms = {
      u_time: { type: 'f', value: 1.0 },
      colorB: { type: 'vec3', value: new THREE.Color(0xfff000) },
      colorA: { type: 'vec3', value: new THREE.Color(0xffffff) }
    }

    this.renderer = new THREE.WebGLRenderer({
      antialias: true
    })

    this.renderer.setSize(this.container.clientWidth, this.container.clientHeight)
    container.appendChild(this.renderer.domElement)

    const ambientLight = new THREE.AmbientLight(0xffffff, 0.7)
    ambientLight.castShadow = false
    this.scene.add(ambientLight)

    const spotLight = new THREE.SpotLight(0xffffff, 0.55)
    spotLight.castShadow = true
    spotLight.position.set(0, 80, 10)
    this.scene.add(spotLight)

    this.onWindowResize = () => {
      this.camera.aspect = this.container.clientWidth / this.container.clientHeight
      this.camera.updateProjectionMatrix()
      this.renderer.setSize(this.container.clientWidth, this.container.clientHeight)
    }

    this.onDocumentWheel = (event: WheelEvent) => {
      const val = this.camera.position.z + event.deltaY / 100
      this.camera.position.z = Math.min(Math.max(val, 84), 256)
    }

    window.addEventListener('resize', this.onWindowResize, false)
    document.addEventListener('wheel', this.onDocumentWheel, false)
  }

  animate () {
    requestAnimationFrame(this.animate.bind(this))
    this.render()
  }

  render () {
    this.uniforms.u_time.value += this.clock.getDelta()
    this.renderer.render(this.scene, this.camera)
  }

  destroy () {
    window.removeEventListener('resize', this.onWindowResize, false)
    document.removeEventListener('wheel', this.onDocumentWheel, false)
    this.renderer.domElement.remove()
    this.renderer.dispose()
  }
}
