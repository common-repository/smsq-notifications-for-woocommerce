<?php
global $smsq_settings, $wpml_activo;

//Control de tabulación
$tab = 1;

//WPML
if ( function_exists( 'icl_register_string' ) || !$wpml_activo ) { //Versión anterior a la 3.2
	$mensaje_pedido		= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_pedido', $smsq_settings['mensaje_pedido'] ) : $smsq_settings['mensaje_pedido'];
	$mensaje_recibido	= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_recibido', $smsq_settings['mensaje_recibido'] ) : $smsq_settings['mensaje_recibido'];
	$mensaje_procesando	= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_procesando', $smsq_settings['mensaje_procesando'] ) : $smsq_settings['mensaje_procesando'];
	$mensaje_completado	= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_completado', $smsq_settings['mensaje_completado'] ) : $smsq_settings['mensaje_completado'];
	$mensaje_canceledado	= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_canceledado', $smsq_settings['mensaje_canceledado'] ) : $smsq_settings['mensaje_canceledado'];
	$mensaje_nota		= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_nota', $smsq_settings['mensaje_nota'] ) : $smsq_settings['mensaje_nota'];
} else if ( $wpml_activo ) { //Versión 3.2 o superior
	$mensaje_pedido		= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_pedido'], 'sms_q', 'mensaje_pedido' );
	$mensaje_recibido	= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_recibido'], 'sms_q', 'mensaje_recibido' );
	$mensaje_procesando	= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_procesando'], 'sms_q', 'mensaje_procesando' );
	$mensaje_completado	= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_completado'], 'sms_q', 'mensaje_completado' );
	$mensaje_canceledado	= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_canceledado'], 'sms_q', 'mensaje_canceledado' );
	$mensaje_nota		= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_nota'], 'sms_q', 'mensaje_nota' );
}

//Listado de proveedores SMS
$listado_de_proveedores = array( 
	"smsq_esms" 		=> "SMSQ",
);
asort( $listado_de_proveedores, SORT_NATURAL | SORT_FLAG_CASE ); //Ordena alfabeticamente los proveedores

//Campos necesarios para cada proveedor
$campos_de_proveedores = array( 
	"smsq_esms" 	=> array( 
		"clave_smsq_esms" 				=> __( 'API Key', 'smsq-notifications-for-woocommerce' ),
		"apitoken_smsq" 				=> __( 'Client Id', 'mimsms-wc-sms-notifications' ),
		"identificador_smsq_esms" 		=> __( 'sender ID', 'smsq-notifications-for-woocommerce' ),
	),
);

//Opciones de campos de selección de los proveedores
$opciones_de_proveedores = array(
	"sms_type_smsq_esms"	=> array(
		"text"					=> __( 'Text', 'smsq-notifications-for-woocommerce' ), 
		"unicode"				=> __( 'Unicode', 'smsq-notifications-for-woocommerce' ), 
	),
);

//Listado de estados de pedidos
$listado_de_estados				= wc_get_order_statuses();
$listado_de_estados_temporal	= array();
$estados_originales				= array( 
	'pending',
	'failed',
	'on-hold',
	'processing',
	'completed',
	'refunded',
	'cancelled',
);
foreach( $listado_de_estados as $clave => $estado ) {
	$nombre_de_estado = str_replace( "wc-", "", $clave );
	if ( !in_array( $nombre_de_estado, $estados_originales ) ) {
		$listado_de_estados_temporal[$estado] = $nombre_de_estado;
	}
}
$listado_de_estados = $listado_de_estados_temporal;

//Listado de mensajes personalizados
$listado_de_mensajes = array(
	'todos'					=> __( 'All messages', 'smsq-notifications-for-woocommerce' ),
	'mensaje_pedido'		=> __( 'Owner custom message', 'smsq-notifications-for-woocommerce' ),
	'mensaje_recibido'		=> __( 'Order on-hold custom message', 'smsq-notifications-for-woocommerce' ),
	'mensaje_procesando'	=> __( 'Order processing custom message', 'smsq-notifications-for-woocommerce' ),
	'mensaje_completado'	=> __( 'Order completed custom message', 'smsq-notifications-for-woocommerce' ),
	'mensaje_canceledado'	=> __( 'Order cancelled custom message', 'smsq-notifications-for-woocommerce' ),
	'mensaje_nota'			=> __( 'Notes custom message', 'smsq-notifications-for-woocommerce' ),
);

/*
Pinta el campo select con el listado de proveedores
*/
function sms_q_listado_de_proveedores( $listado_de_proveedores ) {
	global $smsq_settings;
	
	foreach ( $listado_de_proveedores as $valor => $proveedor ) {
		$chequea = ( isset( $smsq_settings['servicio'] ) && $smsq_settings['servicio'] == $valor ) ? ' selected="selected"' : '';
		echo '<option value="' . $valor . '"' . $chequea . '>' . $proveedor . '</option>' . PHP_EOL;
	}
}

/*
Pinta los campos de los proveedores
*/
function sms_q_campos_de_proveedores( $listado_de_proveedores, $campos_de_proveedores, $opciones_de_proveedores ) {
	global $smsq_settings, $tab;
	
	foreach ( $listado_de_proveedores as $valor => $proveedor ) {
		foreach ( $campos_de_proveedores[$valor] as $valor_campo => $campo ) {
			if ( array_key_exists( $valor_campo, $opciones_de_proveedores ) ) { //Campo select
				echo '
  <tr valign="top" class="' . $valor . '"><!-- ' . $proveedor . ' -->
	<th scope="row" class="titledesc"> <label for="smsq_settings[' . $valor_campo . ']">' .ucfirst( $campo ) . ':' . '
	  <span class="woocommerce-help-tip" data-tip="' . sprintf( __( 'The %s for your account in %s', 'smsq-notifications-for-woocommerce' ), $campo, $proveedor ) . '"></span></label></th>
	<td class="forminp forminp-number"><select class="wc-enhanced-select" id="smsq_settings[' . $valor_campo . ']" name="smsq_settings[' . $valor_campo . ']" tabindex="' . $tab++ . '">
				';
				foreach ( $opciones_de_proveedores[$valor_campo] as $valor_opcion => $opcion ) {
					$chequea = ( isset( $smsq_settings[$valor_campo] ) && $smsq_settings[$valor_campo] == $valor_opcion ) ? ' selected="selected"' : '';
					echo '<option value="' . $valor_opcion . '"' . $chequea . '>' . $opcion . '</option>' . PHP_EOL;
				}
				echo '          </select></td>
  </tr>
				';
			} else { //Campo input
				echo '
  <tr valign="top" class="' . $valor . '"><!-- ' . $proveedor . ' -->
	<th scope="row" class="titledesc"> <label for="smsq_settings[' . $valor_campo . ']">' . ucfirst( $campo ) . ':' . '
	  <span class="woocommerce-help-tip" data-tip="' . sprintf( __( 'The %s for your account in %s', 'smsq-notifications-for-woocommerce' ), $campo, $proveedor ) . '"></span></label></th>
	<td class="forminp forminp-number"><input type="text" id="smsq_settings[' . $valor_campo . ']" name="smsq_settings[' . $valor_campo . ']" size="50" value="' . ( isset( $smsq_settings[$valor_campo] ) ? $smsq_settings[$valor_campo] : '' ) . '" tabindex="' . $tab++ . '" /></td>
  </tr>
				';
			}
		}
	}
}

/*
Pinta los campos del formulario de envío
*/
function sms_q_campos_de_envio() {
	global $smsq_settings;

	$pais					= new WC_Countries();
	$campos					= $pais->get_address_fields( $pais->get_base_country(), 'shipping_' ); //Campos ordinarios
	$campos_personalizados	= apply_filters( 'woocommerce_checkout_fields', array() );
	if ( isset( $campos_personalizados['shipping'] ) ) {
		$campos += $campos_personalizados['shipping'];
	}
	foreach ( $campos as $valor => $campo ) {
		$chequea = ( isset( $smsq_settings['campo_envio'] ) && $smsq_settings['campo_envio'] == $valor ) ? ' selected="selected"' : '';
		if ( isset( $campo['label'] ) ) {
			echo '<option value="' . $valor . '"' . $chequea . '>' . $campo['label'] . '</option>' . PHP_EOL;
		}
	}
}

/*
Pinta el campo select con el listado de estados de pedido
*/
function sms_q_listado_de_estados( $listado_de_estados ) {
	global $smsq_settings;

	foreach( $listado_de_estados as $nombre_de_estado => $estado ) {
		$chequea = '';
		if ( isset( $smsq_settings['estados_personalizados'] ) ) {
			foreach ( $smsq_settings['estados_personalizados'] as $estado_personalizado ) {
				if ( $estado_personalizado == $estado ) {
					$chequea = ' selected="selected"';
				}
			}
		}
		echo '<option value="' . $estado . '"' . $chequea . '>' . $nombre_de_estado . '</option>' . PHP_EOL;
	}
}

/*
Pinta el campo select con el listado de mensajes personalizados
*/
function sms_q_listado_de_mensajes( $listado_de_mensajes ) {
	global $smsq_settings;
	
	$chequeado = false;
	foreach ( $listado_de_mensajes as $valor => $mensaje ) {
		if ( isset( $smsq_settings['mensajes'] ) && in_array( $valor, $smsq_settings['mensajes'] ) ) {
			$chequea	= ' selected="selected"';
			$chequeado	= true;
		} else {
			$chequea	= '';
		}
		$texto = ( !isset( $smsq_settings['mensajes'] ) && $valor == 'todos' && !$chequeado ) ? ' selected="selected"' : '';
		echo '<option value="' . $valor . '"' . $chequea . $texto . '>' . $mensaje . '</option>' . PHP_EOL;
	}
}
