import * as THREE from 'three'
import { metallicTexture, normalTexture, roughnessTexture } from '../assets'

export class ThreePBR {
  private readonly normalMap: THREE.Texture
  private readonly roughnessMap: THREE.Texture
  private readonly metallicMap: THREE.Texture
  private readonly normal = 1.
  private readonly roughness = .0
  private readonly metallic = 1.
  private readonly exposure = 2.
  private readonly gamma = 2.2

  constructor () {
    this.normalMap = new THREE.TextureLoader().load(normalTexture)
    this.normalMap.wrapS = THREE.ClampToEdgeWrapping
    this.normalMap.wrapT = THREE.ClampToEdgeWrapping
    this.normalMap.magFilter = THREE.LinearFilter
    this.normalMap.minFilter = THREE.LinearFilter

    this.roughnessMap = new THREE.TextureLoader().load(roughnessTexture)
    this.roughnessMap.wrapS = THREE.ClampToEdgeWrapping
    this.roughnessMap.wrapT = THREE.ClampToEdgeWrapping
    this.roughnessMap.magFilter = THREE.LinearFilter
    this.roughnessMap.minFilter = THREE.LinearFilter

    this.metallicMap = new THREE.TextureLoader().load(metallicTexture)
    this.metallicMap.wrapS = THREE.ClampToEdgeWrapping
    this.metallicMap.wrapT = THREE.ClampToEdgeWrapping
    this.metallicMap.magFilter = THREE.LinearFilter
    this.metallicMap.minFilter = THREE.LinearFilter
  }

  getNormalMap () {
    return this.normalMap
  }

  getRoughnessMap () {
    return this.roughnessMap
  }

  getMetallicMap () {
    return this.metallicMap
  }

  getExposure () {
    return this.exposure
  }

  getGamma () {
    return this.gamma
  }

  getNormal () {
    return this.normal
  }

  getRoughness () {
    return this.roughness
  }

  getMetallic () {
    return this.metallic
  }
}
