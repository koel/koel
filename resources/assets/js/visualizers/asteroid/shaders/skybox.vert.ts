export const skyboxVert =
  `
varying vec3 v_direction;
void main(){
	v_direction = position.xyz;
	gl_Position = projectionMatrix * modelViewMatrix * vec4(position.xyz, 1.);
}
`
