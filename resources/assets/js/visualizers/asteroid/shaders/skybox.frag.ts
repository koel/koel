export const skyboxFrag =
  `
#define A 0.15
#define B 0.50
#define C 0.10
#define D 0.20
#define E 0.02
#define F 0.30

uniform samplerCube u_cubemap;
uniform samplerCube u_cubemap_b;
uniform float cross_fader;
uniform float u_exposure;
uniform float u_gamma;
uniform bool u_show_cubemap;

varying vec3 v_direction;

vec3 Uncharted2Tonemap( vec3 x ){
	return ((x*(A*x+C*B)+D*E)/(x*(A*x+B)+D*F))-E/F;
}

void main( void ){
	vec3 cube_a = pow( abs(textureCube( u_cubemap, v_direction ).rgb), vec3( 2.2 ) );
	vec3 cube_b = pow( abs(textureCube( u_cubemap_b, v_direction ).rgb), vec3( 2.2 ) );

	vec3 color 	= mix(cube_a, cube_b, cross_fader);

	// apply the tone-mapping
	// color 		= Uncharted2Tonemap( color * u_exposure );
	// white balance
	// color		= color * ( 1. / Uncharted2Tonemap( vec3( 20. ) ) );

	// gamma correction
	// color = pow( color, vec3( 1. / u_gamma ) );

	color *= u_show_cubemap ? 1. : 0.;

	gl_FragColor = vec4( color, 1. );
}
`
