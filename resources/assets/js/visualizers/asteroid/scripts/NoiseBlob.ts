import * as THREE from 'three'
import { ThreeSharedRenderer } from './ThreeSharedRenderer'
import { ThreePBR } from './ThreePBR'
import { ThreePointLight } from './ThreePointLight'
import { AudioAnalyzer } from './AudioAnalyzer'
import { nx, nx3js, ny, ny3js, nz, nz3js, px, px3js, py, py3js, pz, pz3js, rect } from '../assets'
import { blobFrag, blobVert, skyboxFrag, skyboxVert } from '../shaders'

export class NoiseBlob {
  private textSprite!: THREE.Texture
  private renderer: ThreeSharedRenderer
  private isInit: boolean
  private readonly showHdr: boolean
  private w: number
  private h: number
  private shaderCubeMap!: THREE.ShaderMaterial
  private pbr!: ThreePBR
  private scene!: THREE.Scene
  private shadowScene!: THREE.Scene
  private shaderMesh!: THREE.ShaderMaterial
  private shaderWire!: THREE.ShaderMaterial
  private shaderPoints!: THREE.ShaderMaterial
  private shaderShadow!: THREE.ShaderMaterial
  private shaderPopPoints!: THREE.ShaderMaterial
  private shaderPopWire!: THREE.ShaderMaterial
  private shaderPopPointsOut!: THREE.ShaderMaterial
  private shaderPopWireOut!: THREE.ShaderMaterial
  private light: ThreePointLight
  private cubeMapB!: THREE.CubeTexture
  private cubeMap!: THREE.CubeTexture
  private analyzer: AudioAnalyzer
  private timer = 0

  constructor (renderer: ThreeSharedRenderer, analyzer: AudioAnalyzer, light: ThreePointLight) {
    this.renderer = renderer
    this.analyzer = analyzer
    this.light = light

    this.isInit = false
    this.showHdr = true

    this.w = renderer.container.clientWidth
    this.h = renderer.container.clientHeight

    this.initTexture()
    this.initShader()
    this.initScene()
    this.initCubeMap()
  }

  destroy () {
    this.renderer.destroy()
    this.light.destroy()

    this.textSprite?.dispose()
    this.shaderCubeMap?.dispose()
    this.shaderMesh?.dispose()
    this.shaderWire?.dispose()
    this.shaderPoints?.dispose()
    this.shaderShadow?.dispose()
    this.shaderPopPoints?.dispose()
    this.shaderPopWire?.dispose()
    this.shaderPopPointsOut?.dispose()
    this.shaderPopWireOut?.dispose()
    this.cubeMapB?.dispose()
    this.cubeMap?.dispose()
  }

  initTexture () {
    this.textSprite = new THREE.TextureLoader().load(rect)
    this.textSprite.wrapS = THREE.ClampToEdgeWrapping
    this.textSprite.wrapT = THREE.ClampToEdgeWrapping
    this.textSprite.magFilter = THREE.LinearFilter
    this.textSprite.minFilter = THREE.LinearFilter
  }

  initShader () {
    const screenRes = 'vec2( ' + this.w.toFixed(1) + ', ' + this.h.toFixed(1) + ')'

    const load = (_vert, _frag) => new THREE.ShaderMaterial({
      defines: {
        SCREEN_RES: screenRes
      },
      uniforms: {
        u_t: { value: 0 },
        u_is_init: { value: false },
        u_audio_high: { value: 0. },
        u_audio_mid: { value: 0. },
        u_audio_bass: { value: 0. },
        u_audio_level: { value: 0. },
        u_audio_history: { value: 0. }
      },
      vertexShader: _vert,
      fragmentShader: _frag
    })

    this.shaderCubeMap = new THREE.ShaderMaterial(
      {
        defines: {
          SCREEN_RES: screenRes
        },
        uniforms: {
          u_cubemap: { value: this.cubeMap },
          u_cubemap_b: { value: this.cubeMapB },
          u_exposure: { value: 2. },
          u_gamma: { value: 2.2 }
        },
        vertexShader: skyboxVert,
        fragmentShader: skyboxFrag
      })

    this.shaderMesh = load(blobVert, blobFrag)
    this.shaderWire = load(blobVert, blobFrag)
    this.shaderPoints = load(blobVert, blobFrag)
    this.shaderShadow = load(blobVert, blobFrag)
    this.shaderPopPoints = load(blobVert, blobFrag)
    this.shaderPopWire = load(blobVert, blobFrag)
    this.shaderPopPointsOut = load(blobVert, blobFrag)
    this.shaderPopWireOut = load(blobVert, blobFrag)

    this.shaderMesh.extensions.derivatives = true

    this.shaderMesh.defines.IS_MESH = 'true'
    this.shaderMesh.defines.HAS_SHADOW = 'true'
    this.shaderWire.defines.IS_WIRE = 'true'
    this.shaderPoints.defines.IS_POINTS = 'true'
    this.shaderShadow.defines.IS_SHADOW = 'true'
    this.shaderPopPoints.defines.IS_POINTS = 'true'
    this.shaderPopPoints.defines.IS_POP = 'true'
    this.shaderPopWire.defines.IS_WIRE = 'true'
    this.shaderPopWire.defines.IS_POP = 'true'
    this.shaderPopPointsOut.defines.IS_POINTS = 'true'
    this.shaderPopPointsOut.defines.IS_POP_OUT = 'true'
    this.shaderPopWireOut.defines.IS_WIRE = 'true'
    this.shaderPopWireOut.defines.IS_POP_OUT = 'true'

    const lightPosition = this.light.getLightPosition()
    lightPosition.applyMatrix4(this.renderer.camera.modelViewMatrix)

    const shadowMatrix = new THREE.Matrix4()
    shadowMatrix.identity()
    shadowMatrix.multiplyMatrices(
      this.light.getLight().projectionMatrix,
      this.light.getLight().modelViewMatrix
    )

    this.shaderMesh.uniforms.u_light_pos = { value: lightPosition }
    this.shaderMesh.uniforms.u_shadow_matrix = { value: shadowMatrix }
    this.shaderMesh.uniforms.u_shadow_map = { value: this.light.getShadowMap() }
    this.shaderMesh.uniforms.u_debug_shadow = { value: false }
    this.shaderPoints.uniforms.textSprite = { value: this.textSprite }
    this.shaderPopPoints.uniforms.textSprite = { value: this.textSprite }
    this.shaderPopWire.uniforms.textSprite = { value: this.textSprite }
    this.shaderPopPointsOut.uniforms.textSprite = { value: this.textSprite }
    this.shaderPopWireOut.uniforms.textSprite = { value: this.textSprite }

    this.shaderPoints.blending = THREE.AdditiveBlending
    this.shaderWire.blending = THREE.AdditiveBlending
    this.shaderPopPoints.blending = THREE.AdditiveBlending
    this.shaderPopWire.blending = THREE.AdditiveBlending
    this.shaderPopPointsOut.blending = THREE.AdditiveBlending
    this.shaderPopWireOut.blending = THREE.AdditiveBlending

    this.shaderWire.transparent = true
    this.shaderPoints.transparent = true
    this.shaderPopPoints.transparent = true
    this.shaderPopWire.transparent = true
    this.shaderPopPointsOut.transparent = true
    this.shaderPopWireOut.transparent = true

    this.shaderWire.depthTest = false
    this.shaderPoints.depthTest = false
    this.shaderPopPoints.depthTest = false
    this.shaderPopWire.depthTest = false
    this.shaderPopPointsOut.depthTest = false
    this.shaderPopWireOut.depthTest = false
  }

  initScene () {
    const _sphere_size = .7
    const _geom = new THREE.SphereGeometry(_sphere_size, 128, 128)
    const _geom_lowres = new THREE.SphereGeometry(_sphere_size, 64, 64)

    this.scene = new THREE.Scene()
    this.shadowScene = new THREE.Scene()

    const _mesh = new THREE.Mesh(_geom, this.shaderMesh)
    const _wire = new THREE.Line(_geom_lowres, this.shaderWire)
    const _points = new THREE.Points(_geom, this.shaderPoints)
    const _shadow_mesh = new THREE.Mesh(_geom, this.shaderShadow)

    const _pop_points = new THREE.Points(_geom_lowres, this.shaderPopPoints)
    const _pop_wire = new THREE.Line(_geom_lowres, this.shaderPopWire)

    const _pop_points_out = new THREE.Points(_geom_lowres, this.shaderPopPointsOut)
    const _pop_wire_out = new THREE.Line(_geom_lowres, this.shaderPopWireOut)

    this.scene.add(_mesh)
    this.scene.add(_wire)
    this.scene.add(_points)

    this.scene.add(_pop_points)
    this.scene.add(_pop_wire)
    this.scene.add(_pop_points_out)
    this.scene.add(_pop_wire_out)

    this.shadowScene.add(_shadow_mesh)

    const boxGeometry = new THREE.BoxGeometry(100, 100, 100)
    const mesh = new THREE.Mesh(boxGeometry, this.shaderCubeMap)

    const mS = (new THREE.Matrix4()).identity()
    mS.elements[0] = -1
    mS.elements[5] = -1
    mS.elements[10] = -1

    boxGeometry.applyMatrix4(mS)

    this.scene.add(mesh)
  }

  initCubeMap () {
    this.cubeMap = new THREE.CubeTextureLoader().load([
      px3js, nx3js,
      py3js, ny3js,
      pz3js, nz3js
    ])

    this.cubeMap.format = THREE.RGBAFormat

    this.cubeMapB = new THREE.CubeTextureLoader().load([
      px, nx,
      py, ny,
      pz, nz
    ])

    this.cubeMapB.format = THREE.RGBAFormat

    this.shaderMesh.uniforms.cubemap = { value: this.cubeMap }
    this.shaderCubeMap.uniforms.u_cubemap.value = this.cubeMap
    this.shaderMesh.uniforms.cubemap_b = { value: this.cubeMapB }
    this.shaderCubeMap.uniforms.u_cubemap_b.value = this.cubeMapB
    this.shaderCubeMap.uniforms.u_show_cubemap = { value: this.showHdr }
    this.shaderMesh.defines.HAS_CUBEMAP = 'true'
  }

  updateCubeMap () {
    const crossFader = 0.
    this.shaderMesh.uniforms.cross_fader = { value: crossFader }
    this.shaderCubeMap.uniforms.cross_fader = { value: crossFader }

    this.shaderCubeMap.uniforms.u_exposure.value = this.pbr.getExposure()
    this.shaderCubeMap.uniforms.u_gamma.value = this.pbr.getGamma()
  }

  update () {
    const shaders = [
      this.shaderMesh,
      this.shaderWire,
      this.shaderPoints,
      this.shaderPopPoints,
      this.shaderPopWire,
      this.shaderPopPointsOut,
      this.shaderPopWireOut,
      this.shaderShadow
    ]

    for (let i = 0; i < shaders.length; i++) {
      shaders[i].uniforms.u_is_init.value = this.isInit
      shaders[i].uniforms.u_t.value = this.timer

      shaders[i].uniforms.u_audio_high.value = this.analyzer.getHigh()
      shaders[i].uniforms.u_audio_mid.value = this.analyzer.getMid()
      shaders[i].uniforms.u_audio_bass.value = this.analyzer.getBass()
      shaders[i].uniforms.u_audio_level.value = this.analyzer.getLevel()
      shaders[i].uniforms.u_audio_history.value = this.analyzer.getHistory()
    }

    this.updateCubeMap()

    const _cam = this.renderer.getCamera()
    this.renderer.renderer.render(this.scene, _cam)

    if (!this.isInit) {
      this.isInit = true
    }

    this.timer = this.renderer.getTimer()
  }

  setRetina () {
    this.w *= .5
    this.h *= .5
  }

  setPBR (pbr: ThreePBR) {
    this.pbr = pbr

    this.shaderMesh.uniforms.tex_normal = { value: this.pbr.getNormalMap() }
    this.shaderMesh.uniforms.tex_roughness = { value: this.pbr.getRoughnessMap() }
    this.shaderMesh.uniforms.tex_metallic = { value: this.pbr.getMetallicMap() }

    this.shaderMesh.uniforms.u_normal = { value: this.pbr.getNormal() }
    this.shaderMesh.uniforms.u_roughness = { value: this.pbr.getRoughness() }
    this.shaderMesh.uniforms.u_metallic = { value: this.pbr.getMetallic() }

    this.shaderMesh.uniforms.u_exposure = { value: this.pbr.getExposure() }
    this.shaderMesh.uniforms.u_gamma = { value: this.pbr.getGamma() }

    this.shaderMesh.uniforms.u_view_matrix_inverse = { value: this.renderer.getInverseMatrix() }

    this.shaderMesh.defines.IS_PBR = 'true'
  }

  updatePBR () {
    this.shaderMesh.uniforms.u_normal.value = this.pbr.getNormal()
    this.shaderMesh.uniforms.u_roughness.value = this.pbr.getRoughness()
    this.shaderMesh.uniforms.u_metallic.value = this.pbr.getMetallic()

    this.shaderMesh.uniforms.u_exposure.value = this.pbr.getExposure()
    this.shaderMesh.uniforms.u_gamma.value = this.pbr.getGamma()

    this.shaderMesh.uniforms.u_view_matrix_inverse.value = this.renderer.getInverseMatrix()
  }
}
