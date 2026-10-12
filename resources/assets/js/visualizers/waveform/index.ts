import * as THREE from 'three'
import SceneInit from './lib/SceneInit'
import shaders from './lib/shaders'
import { configureAnalyser } from '@/visualizers/analyser'
import { startFrameLoop } from '@/visualizers/frameLoop'

export const init = (container: HTMLElement) => {
  const sceneInit = new SceneInit(container)

  const analyser = configureAnalyser({ fftSize: 512 })
  const dataArray = new Uint8Array(analyser.frequencyBinCount)

  const uniforms = {
    u_time: {
      type: 'f',
      value: 1.0,
    },
    u_amplitude: {
      type: 'f',
      value: 3.0,
    },
    u_data_arr: {
      type: 'float[64]',
      value: dataArray,
    },
  }

  const planeGeometry = new THREE.PlaneGeometry(64, 64, 64, 64)

  const planeCustomMaterial = new THREE.ShaderMaterial({
    uniforms,
    vertexShader: shaders.vertex,
    fragmentShader: shaders.fragment,
    wireframe: true,
  })

  const planeMesh = new THREE.Mesh(planeGeometry, planeCustomMaterial)
  planeMesh.rotation.x = -Math.PI / 2 + Math.PI / 4
  planeMesh.scale.x = 2
  planeMesh.scale.y = 2
  planeMesh.scale.z = 2
  planeMesh.position.y = 8
  sceneInit.scene.add(planeMesh)

  const stopFrameLoop = startFrameLoop(() => {
    analyser.getByteFrequencyData(dataArray)
    sceneInit.render()
  })

  return () => {
    stopFrameLoop()
    sceneInit.destroy()
  }
}
