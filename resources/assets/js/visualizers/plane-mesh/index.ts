import * as THREE from 'three'
import shaders from './shaders'
import planeMeshParameters from './planeMeshParameters'
import { audioService } from '@/services'

export const init = (container: HTMLElement) => {
  const uniforms = {
    u_time: {
      type: 'f',
      value: 2.0
    },
    u_amplitude: {
      type: 'f',
      value: 4.0
    },
    u_data_arr: {
      type: 'float[64]',
      value: new Uint8Array()
    }
  }

  const analyser = audioService.analyzer

  analyser.fftSize = 1024
  const dataArray = new Uint8Array(analyser.frequencyBinCount)

  const width = container.clientWidth
  const height = container.clientHeight
  const scene = new THREE.Scene()

  const ambientLight = new THREE.AmbientLight(0xaaaaaa)
  ambientLight.castShadow = false

  const spotLight = new THREE.SpotLight(0xffffff)
  spotLight.intensity = 0.9
  spotLight.position.set(-10, 40, 20)
  spotLight.castShadow = true

  const camera = new THREE.PerspectiveCamera(
    85,
    width / height,
    1,
    1000
  )
  camera.position.z = 80

  const renderer = new THREE.WebGLRenderer()
  renderer.setSize(width, height)
  renderer.setClearAlpha(0)

  container.appendChild(renderer.domElement)

  const planeGeometry = new THREE.PlaneGeometry(64, 64, 64, 64)
  const planeMaterial = new THREE.ShaderMaterial({
    uniforms,
    vertexShader: shaders.vertex,
    fragmentShader: shaders.fragment,
    wireframe: true
  })

  planeMeshParameters.forEach(item => {
    const planeMesh = new THREE.Mesh(planeGeometry, planeMaterial)

    if (item.rotation.x == undefined) {
      planeMesh.rotation.y = item.rotation.y
    } else {
      planeMesh.rotation.x = item.rotation.x
    }

    planeMesh.scale.x = item.scale
    planeMesh.scale.y = item.scale
    planeMesh.scale.z = item.scale
    planeMesh.position.x = item.position.x
    planeMesh.position.y = item.position.y
    planeMesh.position.z = item.position.z

    scene.add(planeMesh)
  })

  scene.add(ambientLight)
  scene.add(spotLight)

  const render = () => {
    analyser.getByteFrequencyData(dataArray)
    uniforms.u_data_arr.value = dataArray
    camera.rotation.z += 0.001
    renderer.render(scene, camera)
  }

  const animate = () => {
    requestAnimationFrame(animate)
    render()
  }

  const windowResizeHandler = () => {
    const width = container.clientWidth
    const height = container.clientHeight
    renderer.setSize(width, height)
    camera.aspect = width / height
    camera.updateProjectionMatrix()
    renderer.domElement.width = width
    renderer.domElement.height = height
  }

  const wheelHandler = event => {
    const val = camera.position.z + event.deltaY / 100
    camera.position.z = Math.min(Math.max(val, 0), 256)
  }

  window.addEventListener('resize', windowResizeHandler)
  document.addEventListener('wheel', wheelHandler)

  animate()

  return () => {
    renderer.domElement.remove()
    renderer.dispose()
    window.removeEventListener('resize', windowResizeHandler)
    document.removeEventListener('wheel', wheelHandler)
  }
}
