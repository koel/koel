import * as THREE from 'three'

export class ThreePointLight {
  private readonly shadowBuffer: THREE.WebGLRenderTarget
  private readonly light: THREE.PerspectiveCamera

  constructor () {
    this.shadowBuffer = new THREE.WebGLRenderTarget(2048., 2048.)
    this.shadowBuffer.depthBuffer = true
    this.shadowBuffer.depthTexture = new THREE.DepthTexture(0, 0)

    this.light = new THREE.PerspectiveCamera(35., this.shadowBuffer.width / this.shadowBuffer.height, .1, 1000.)
    this.light.lookAt(0., 0., 0.)
  }

  ziggle (frame: number) {
    const e = frame * 10.

    this.light.position.copy(new THREE.Vector3(
      this.light.position.x * Math.sin(e),
      this.light.position.y,
      this.light.position.z * Math.cos(e)
    ))

    this.light.lookAt(0., 0., 0.)
    this.light.updateProjectionMatrix()
  }

  getLight () {
    return this.light
  }

  getLightPosition () {
    return this.light.position
  }

  getShadowMap () {
    return this.shadowBuffer.depthTexture
  }

  destroy () {
    this.shadowBuffer.dispose()
  }
}
