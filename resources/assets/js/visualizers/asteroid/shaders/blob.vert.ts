export const blobVert =
  `
uniform vec2 u_mouse;
uniform vec2 u_mouse_delta;
uniform float u_t;
uniform bool u_is_init;

uniform float u_audio_high;
uniform float u_audio_mid;
uniform float u_audio_bass;
uniform float u_audio_level;
uniform float u_audio_history;

varying float v_noise;




#if defined(IS_PBR) && defined(HAS_CUBEMAP)
	uniform mat4 u_view_matrix_inverse;

	varying vec3 v_world_normal;
	varying vec3 v_eye_pos;
	varying vec3 v_object_pos;
	varying vec3 v_pos;
	varying vec3 v_normal;
	varying vec3 v_world_pos;
	varying vec2 v_uv;
#endif



#if defined(HAS_SHADOW)
	uniform mat4 u_shadow_matrix;
	varying vec4 v_shadow_coord;

	const mat4 biasMat  = mat4(	0.5, 0.0, 0.0, 0.0,
							0.0, 0.5, 0.0, 0.0,
							0.0, 0.0, 0.5, 0.0,
							0.5, 0.5, 0.5, 1.0 );
#endif


// (Keijiro) This shader was slightly modified from the original version.
// It's recommended to use the original version for other purposes.

//
// Description : Array and textureless GLSL 2D/3D/4D simplex
//               noise functions.
//      Author : Ian McEwan, Ashima Arts.
//  Maintainer : ijm
//     Lastmod : 20110822 (ijm)
//     License : Copyright (C) 2011 Ashima Arts. All rights reserved.
//               Distributed under the MIT License. See LICENSE file.
//               https://github.com/ashima/webgl-noise
//

vec3 mod289(vec3 x)
{
    return x - floor(x * (1.0 / 289.0)) * 289.0;
}

vec4 mod289(vec4 x) {
    return x - floor(x * (1.0 / 289.0)) * 289.0;
}

vec4 permute(vec4 x)
{
    return mod289((x * 34.0 + 1.0) * x);
}

vec4 taylorInvSqrt(vec4 r)
{
    return 1.79284291400159 - 0.85373472095314 * r;
}

float snoise(vec3 v)
{
    const vec2 C = vec2(1.0 / 6.0, 1.0 / 3.0);

    // First corner
    vec3 i  = floor(v + dot(v, C.yyy));
    vec3 x0 = v   - i + dot(i, C.xxx);

    // Other corners
    vec3 g = step(x0.yzx, x0.xyz);
    vec3 l = 1.0 - g;
    vec3 i1 = min(g.xyz, l.zxy);
    vec3 i2 = max(g.xyz, l.zxy);

    // x1 = x0 - i1  + 1.0 * C.xxx;
    // x2 = x0 - i2  + 2.0 * C.xxx;
    // x3 = x0 - 1.0 + 3.0 * C.xxx;
    vec3 x1 = x0 - i1 + C.xxx;
    vec3 x2 = x0 - i2 + C.yyy;
    vec3 x3 = x0 - 0.5;

    // Permutations
    i = mod289(i); // Avoid truncation effects in permutation
    vec4 p =
      permute(permute(permute(i.z + vec4(0.0, i1.z, i2.z, 1.0))
                            + i.y + vec4(0.0, i1.y, i2.y, 1.0))
                            + i.x + vec4(0.0, i1.x, i2.x, 1.0));

    // Gradients: 7x7 points over a square, mapped onto an octahedron.
    // The ring size 17*17 = 289 is close to a multiple of 49 (49*6 = 294)
    vec4 j = p - 49.0 * floor(p * (1.0 / 49.0));  // mod(p,7*7)

    vec4 x_ = floor(j * (1.0 / 7.0));
    vec4 y_ = floor(j - 7.0 * x_ );  // mod(j,N)

    vec4 x = x_ * (2.0 / 7.0) + 0.5 / 7.0 - 1.0;
    vec4 y = y_ * (2.0 / 7.0) + 0.5 / 7.0 - 1.0;

    vec4 h = 1.0 - abs(x) - abs(y);

    vec4 b0 = vec4(x.xy, y.xy);
    vec4 b1 = vec4(x.zw, y.zw);

    //vec4 s0 = vec4(lessThan(b0, 0.0)) * 2.0 - 1.0;
    //vec4 s1 = vec4(lessThan(b1, 0.0)) * 2.0 - 1.0;
    vec4 s0 = floor(b0) * 2.0 + 1.0;
    vec4 s1 = floor(b1) * 2.0 + 1.0;
    vec4 sh = -step(h, vec4(0.0));

    vec4 a0 = b0.xzyw + s0.xzyw * sh.xxyy;
    vec4 a1 = b1.xzyw + s1.xzyw * sh.zzww;

    vec3 g0 = vec3(a0.xy, h.x);
    vec3 g1 = vec3(a0.zw, h.y);
    vec3 g2 = vec3(a1.xy, h.z);
    vec3 g3 = vec3(a1.zw, h.w);

    // Normalise gradients
    vec4 norm = taylorInvSqrt(vec4(dot(g0, g0), dot(g1, g1), dot(g2, g2), dot(g3, g3)));
    g0 *= norm.x;
    g1 *= norm.y;
    g2 *= norm.z;
    g3 *= norm.w;

    // Mix final noise value
    vec4 m = max(0.6 - vec4(dot(x0, x0), dot(x1, x1), dot(x2, x2), dot(x3, x3)), 0.0);
    m = m * m;
    m = m * m;

    vec4 px = vec4(dot(x0, g0), dot(x1, g1), dot(x2, g2), dot(x3, g3));
    return (42.0 * dot(m, px) + 1.) * .5;
}

vec3 norm(in vec3 _v){
	return length(_v) > .0 ? normalize(_v) : vec3(.0);
}

mat4 rotationMatrix(vec3 axis, float angle)
{
    axis = norm(axis);
    float s = sin(angle);
    float c = cos(angle);
    float oc = 1.0 - c;

    return mat4(oc * axis.x * axis.x + c,           oc * axis.x * axis.y - axis.z * s,  oc * axis.z * axis.x + axis.y * s,  0.0,
                oc * axis.x * axis.y + axis.z * s,  oc * axis.y * axis.y + c,           oc * axis.y * axis.z - axis.x * s,  0.0,
                oc * axis.z * axis.x - axis.y * s,  oc * axis.y * axis.z + axis.x * s,  oc * axis.z * axis.z + c,           0.0,
                0.0,                                0.0,                                0.0,                                1.0);
}

void main(){
	float m_bass = u_audio_bass;
	float m_mid = u_audio_mid;
	float m_high = u_audio_high;
	float m_level = u_audio_level;
	float m_history = u_audio_history;

	vec3 m_noise_seed = position.xyz;
	float m_noise_complexity = .6;
	float m_noise_time = u_audio_history * .3;
	float m_noise_scale = 1.2 + m_level;

	vec3 m_tangent_vector = .00001 * norm(cross(position, vec3(1., 0., 0.))
							+ cross(position, vec3(0., 1., 0.)));
	vec3 m_bitangent_vector = .00001 * norm(cross(m_tangent_vector, position));

    float m_fbm = 0.;
    float m_fbm_tangent = 0.;
    float m_fbm_bitangent = 0.;

    const int m_noise_oct = 5;
    for(int i = 0; i < m_noise_oct; i++){
    	m_fbm += snoise(
    		(m_noise_seed) * m_noise_complexity * float(i) +
    		m_noise_time * float(i)
    	);
    	m_fbm_tangent += snoise(
    		(m_noise_seed + m_tangent_vector) * m_noise_complexity * float(i) +
    		m_noise_time * float(i)
    	);
    	m_fbm_bitangent += snoise(
    		(m_noise_seed + m_bitangent_vector) * m_noise_complexity * float(i) +
    		m_noise_time * float(i)
    	);
    }
    m_fbm /= (float(m_noise_oct));
    m_fbm_tangent /= (float(m_noise_oct));
    m_fbm_bitangent /= (float(m_noise_oct));

    vec3 m_pos = position + norm(position) * m_fbm * m_noise_scale;
    vec3 m_pos_tangent = (position + m_tangent_vector) + norm(position + m_tangent_vector) * m_fbm * m_noise_scale;
    vec3 m_pos_bitangent = (position + m_bitangent_vector) + norm(position + m_bitangent_vector) * m_fbm * m_noise_scale;

    vec3 m_normal = norm(cross( (m_pos_tangent - m_pos), (m_pos_bitangent - m_pos)));


	// get color
    float m_noise_col = pow(abs(1.-m_fbm), 3.5);
    v_noise = m_noise_col + m_noise_col * m_level * 2.2;

    // rand direction
    float _dirx = snoise(m_pos.zyx * 4. + m_noise_time * .01);
	float _diry = snoise(m_pos.yzx * 4. + m_noise_time * .01);
	float _dirz = snoise(m_pos.zxy * 4. + m_noise_time * .01);
	vec3 _rand_point_dir = vec3(_dirx, _diry, _dirz);
	_rand_point_dir = 1.-2.*_rand_point_dir;

#if defined(IS_WIRE) || defined(IS_POINTS)
	// size
	gl_PointSize = pow(abs(m_fbm), 6.) * 1000. * m_high;

	m_pos += (_rand_point_dir * .3 * m_level);
#endif

#if defined(IS_POP)
	gl_PointSize *= .5;
	m_pos *= 1.1 * m_fbm;
	m_pos = vec3(rotationMatrix(vec3(.3,1.,.2), .5*m_history) * vec4(m_pos, 1.));
#endif
#if defined(IS_POP_OUT)
	gl_PointSize *= .5;
	m_pos *= 1.2;

	m_pos += (_rand_point_dir*_rand_point_dir * .2 * m_high);
	m_pos = vec3(rotationMatrix(vec3(1.,.2,.3), -.5*m_history) * vec4(m_pos, 1.));
#endif



#if defined(IS_PBR) && defined(HAS_CUBEMAP)
	vec4 _world_pos	= modelMatrix * vec4(m_pos, 1.);
    vec4 _view_pos	= viewMatrix * _world_pos;

    v_object_pos = m_pos;
    v_pos = _view_pos.xyz;
	v_normal = normalMatrix * m_normal;
	v_world_pos = _world_pos.xyz;
	v_world_normal = vec3(u_view_matrix_inverse * vec4(v_normal, 0.));
	v_eye_pos = -1. * vec3(u_view_matrix_inverse * (_view_pos - vec4(0.,0.,0.,1.)) );
	v_uv = uv;

#endif

#if defined(HAS_SHADOW)
	v_shadow_coord = (biasMat * u_shadow_matrix) * vec4(m_pos, 1.);
#endif

	gl_Position = projectionMatrix * modelViewMatrix * vec4(m_pos, 1.);
}
`
