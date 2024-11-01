<?php
//Definimos las variables
$sms_q = array( 	
	'plugin' 		=> 'SMSQ Notifications for WooCommerce', 
	'plugin_uri' 	=> 'smsq-notifications-for-woocommerce', 
	'donacion' 		=> 'https://smsq.global',
	'soporte' 		=> 'https://smsq.global',
	'plugin_url' 	=> 'https://smsq.global', 
	'ajustes' 		=> 'admin.php?page=sms_q', 
	'puntuacion' 	=> 'https://smsq.global' 
);

//Carga el idioma
load_plugin_textdomain( 'smsq-notifications-for-woocommerce', null, dirname( DIRECCION_sms_q ) . '/languages' );

//Carga la configuración del plugin
$smsq_settings = get_option( 'smsq_settings' );

//Enlaces adicionales personalizados
function sms_q_enlaces( $enlaces, $archivo ) {
	global $sms_q;

	if ( $archivo == DIRECCION_sms_q ) {
		
		// 
		
	}

	return $enlaces;
}
add_filter( 'plugin_row_meta', 'sms_q_enlaces', 10, 2 );

//Añade el botón de configuración
function sms_q_enlace_de_ajustes( $enlaces ) { 
	global $sms_q;

	$enlaces_de_ajustes = array( 
		'<a href="' . $sms_q['ajustes'] . '" title="' . __( 'Settings of ', 'smsq-notifications-for-woocommerce' ) . $sms_q['plugin'] .'">' . __( 'Settings', 'smsq-notifications-for-woocommerce' ) . '</a>', 
		'<a href="' . $sms_q['soporte'] . '" title="' . __( 'Support of ', 'smsq-notifications-for-woocommerce' ) . $sms_q['plugin'] .'">' . __( 'Support', 'smsq-notifications-for-woocommerce' ) . '</a>' 
	);
	foreach( $enlaces_de_ajustes as $enlace_de_ajustes )	{
		array_unshift( $enlaces, $enlace_de_ajustes );
	}

	return $enlaces; 
}
$plugin = DIRECCION_sms_q; 
add_filter( "plugin_action_links_$plugin", 'sms_q_enlace_de_ajustes' );

//Obtiene toda la información sobre el plugin
function sms_q_plugin( $nombre ) {
	global $sms_q;
	
	$argumentos	= ( object ) array( 
		'slug'		=> $nombre 
	);
	$consulta	= array( 
		'action'	=> 'plugin_information', 
		'timeout'	=> 15, 
		'request'	=> serialize( $argumentos )
	);
	$respuesta	= get_transient( 'sms_q_plugin' );
	if ( false === $respuesta ) {
		$respuesta = wp_remote_post( 'https://api.wordpress.org/plugins/info/1.0/', array( 'body'	=> $consulta ) );
		set_transient( 'sms_q_plugin', $respuesta, 24 * HOUR_IN_SECONDS );
	}
	if ( !is_wp_error( $respuesta ) ) {
		$plugin = get_object_vars( unserialize( $respuesta['body'] ) );
	} else {
		$plugin['rating'] = 100;
	}
	$plugin['rating'] = 100;

	$rating = array(
	   'rating'		=> $plugin['rating'],
	   'type'		=> 'percent',
	   'number'		=> $plugin['num_ratings'],
	);
	ob_start();
	wp_star_rating( $rating );
	$estrellas = ob_get_contents();
	ob_end_clean();

	return '<a title="' . sprintf( __( 'Please, rate %s:', 'smsq-notifications-for-woocommerce' ), $sms_q['plugin'] ) . '" href="' . $sms_q['puntuacion'] . '?rate=5#postform" class="estrellas">' . $estrellas . '</a>';
}

//Hoja de estilo
function sms_q_estilo() {
	wp_register_style( 'sms_q_hoja_de_estilo', plugins_url( 'assets/css/style.css', DIRECCION_sms_q ) ); //Carga la hoja de estilo
	wp_enqueue_style( 'sms_q_hoja_de_estilo' ); //Carga la hoja de estilo
}
add_action( 'admin_enqueue_scripts', 'sms_q_estilo' );

//Eliminamos todo rastro del plugin al desinstalarlo
function sms_q_desinstalar() {
	delete_option( 'smsq_settings' );
	delete_transient( 'sms_q_plugin' );
}
register_uninstall_hook( __FILE__, 'sms_q_desinstalar' );
