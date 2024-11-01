<?php
/*
Plugin Name: SMSQ Notifications for WooCommerce
Version: 1.3
Plugin URI: https://wordpress.org/plugins/smsq-notifications-for-woocommerce/
Description: Add to WooCommerce SMS notifications to your clients for order status changes. Also you can receive an SMS message when the shop get a new order and select if you want to send international SMS. The plugin add the international dial code automatically to the client phone number.
Author URI:  https://smsq.global | https://smsq.com.bd
Author: SMSQ | Powered By Q Technologies Limited
Requires at least: 3.8
Tested up to: 5.6
WC requires at least: 2.1
WC tested up to: 4.0.1

Text Domain: smsq-notifications-for-woocommerce
Domain Path: /languages

@package SMSQ Notifications for WooCommerce
@category Core
@author SMSQ | Powered By Q Technologies Limited
*/

//Igual no deberías poder abrirme
defined( 'ABSPATH' ) || exit;

//Definimos constantes
define( 'DIRECCION_sms_q', plugin_basename( __FILE__ ) );

//Funciones generales de SMSQ
include_once( 'includes/admin/funciones-smsq.php' );

//¿Está activo WooCommerce?
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) || is_network_only_plugin( 'woocommerce/woocommerce.php' ) ) {
	//Cargamos funciones necesarias
	include_once( 'includes/admin/funciones.php' );

	//Comprobamos si está instalado y activo WPML
	$wpml_activo = function_exists( 'icl_object_id' );
	
	//Actualiza las traducciones de los mensajes SMS
	function smsqg_registra_wpml( $smsq_settings ) {
		global $wpml_activo;
		
		//Registramos los textos en WPML
		if ( $wpml_activo && function_exists( 'icl_register_string' ) ) {
			icl_register_string( 'sms_q', 'mensaje_pedido', $smsq_settings['mensaje_pedido'] );
			icl_register_string( 'sms_q', 'mensaje_recibido', $smsq_settings['mensaje_recibido'] );
			icl_register_string( 'sms_q', 'mensaje_procesando', $smsq_settings['mensaje_procesando'] );
			icl_register_string( 'sms_q', 'mensaje_completado', $smsq_settings['mensaje_completado'] );
			icl_register_string( 'sms_q', 'mensaje_canceledado', $smsq_settings['mensaje_canceledado'] );
			icl_register_string( 'sms_q', 'mensaje_nota', $smsq_settings['mensaje_nota'] );
		} else if ( $wpml_activo ) {
			do_action( 'wpml_register_single_string', 'sms_q', 'mensaje_pedido', $smsq_settings['mensaje_pedido'] );
			do_action( 'wpml_register_single_string', 'sms_q', 'mensaje_recibido', $smsq_settings['mensaje_recibido'] );
			do_action( 'wpml_register_single_string', 'sms_q', 'mensaje_procesando', $smsq_settings['mensaje_procesando'] );
			do_action( 'wpml_register_single_string', 'sms_q', 'mensaje_completado', $smsq_settings['mensaje_completado'] );
			do_action( 'wpml_register_single_string', 'sms_q', 'mensaje_canceledado', $smsq_settings['mensaje_canceledado'] );
			do_action( 'wpml_register_single_string', 'sms_q', 'mensaje_nota', $smsq_settings['mensaje_nota'] );
		}
	}
	
	//Inicializamos las traducciones y los proveedores
	function sms_q_inicializacion() {
		global $smsq_settings;

		smsqg_registra_wpml( $smsq_settings );
	}
	add_action( 'init', 'sms_q_inicializacion' );

	//Pinta el formulario de configuración
	function sms_q_tab() {
		include( 'includes/admin/funciones-formulario.php' );
		include( 'includes/formulario.php' );
	}

	//Añade en el menú a WooCommerce
	function sms_q_admin_menu() {
		add_submenu_page( 'woocommerce', __( 'SMSQ | WooCommerce SMS Notifications', 'smsq-notifications-for-woocommerce' ),  __( 'SMSQ Notifications', 'smsq-notifications-for-woocommerce' ) , 'manage_woocommerce', 'sms_q', 'sms_q_tab' );
	}
	add_action( 'admin_menu', 'sms_q_admin_menu', 15 );

	//Carga los scripts y CSS de WooCommerce
	function sms_q_screen_id( $woocommerce_screen_ids ) {
		$woocommerce_screen_ids[] = 'woocommerce_page_sms_q';

		return $woocommerce_screen_ids;
	}
	add_filter( 'woocommerce_screen_ids', 'sms_q_screen_id' );

	//Registra las opciones
	function sms_q_registra_opciones() {
		global $smsq_settings;
	
		register_setting( 'smsq_settings_group', 'smsq_settings', 'sms_q_update' );
		$smsq_settings = get_option( 'smsq_settings' );

		if ( isset( $smsq_settings['estados_personalizados'] ) && !empty( $smsq_settings['estados_personalizados'] ) ) { //Comprueba la existencia de estados personalizados
			foreach ( $smsq_settings['estados_personalizados'] as $estado ) {
				add_action( "woocommerce_order_status_{$estado}", 'sms_q_procesa_estados', 10 );
			}
		}
	}
	add_action( 'admin_init', 'sms_q_registra_opciones' );
	
	function sms_q_update( $smsq_settings ) {
		smsqg_registra_wpml( $smsq_settings );
		
		return $smsq_settings;
	}

	//Procesa el SMS
	function sms_q_procesa_estados( $pedido, $notificacion = false ) {
		global $smsq_settings, $wpml_activo;
		
		$numero_de_pedido	= $pedido;
		$pedido				= new WC_Order( $numero_de_pedido );
		$estado				= is_callable( array( $pedido, 'get_status' ) ) ? $pedido->get_status() : $pedido->status;

		//Comprobamos si se tiene que enviar el mensaje o no
		if ( isset( $smsq_settings['mensajes'] ) ) {
			if ( $estado == 'on-hold' && !array_intersect( array( "todos", "mensaje_pedido", "mensaje_recibido" ), $smsq_settings['mensajes'] ) ) {
				return;
			} else if ( $estado == 'processing' && !array_intersect( array( "todos", "mensaje_pedido", "mensaje_procesando" ), $smsq_settings['mensajes'] ) ) {
				return;
			} else if ( $estado == 'completed' && !array_intersect( array( "todos", "mensaje_completado" ), $smsq_settings['mensajes'] ) ) {
				return;
			} else if ( $estado == 'cancelled' && !array_intersect( array( "todos", "mensaje_canceledado" ), $smsq_settings['mensajes'] ) ) {
				return;
			}
		} else {
			return;
		}
		//Permitir que otros plugins impidan que se envíe el SMS
		if ( !apply_filters( 'sms_q_send_message', true, $pedido ) ) {
			return;
		}

		//Recoge datos del formulario de facturación
		$billing_country		= is_callable( array( $pedido, 'get_billing_country' ) ) ? $pedido->get_billing_country() : $pedido->billing_country;
		$billing_phone			= is_callable( array( $pedido, 'get_billing_phone' ) ) ? $pedido->get_billing_phone() : $pedido->billing_phone;
		$shipping_country		= is_callable( array( $pedido, 'get_shipping_country' ) ) ? $pedido->get_shipping_country() : $pedido->shipping_country;
		$campo_envio			= get_post_meta( $numero_de_pedido, $smsq_settings['campo_envio'], false );
		$campo_envio			= ( isset( $campo_envio[0] ) ) ? $campo_envio[0] : '';
		$telefono				= sms_q_procesa_el_telefono( $pedido, $billing_phone, $smsq_settings['servicio'] );
		$telefono_envio			= sms_q_procesa_el_telefono( $pedido, $campo_envio, $smsq_settings['servicio'], false, true );
		$enviar_envio			= ( $telefono != $telefono_envio && isset( $smsq_settings['envio'] ) && $smsq_settings['envio'] == 1 ) ? true : false;
		$internacional			= ( $billing_country && ( WC()->countries->get_base_country() != $billing_country ) ) ? true : false;
		$internacional_envio	= ( $shipping_country && ( WC()->countries->get_base_country() != $shipping_country ) ) ? true : false;
		//Teléfono propietario
		if ( strpos( $smsq_settings['telefono'], "|" ) ) {
			$administradores = explode( "|", $smsq_settings['telefono'] ); //Existe más de uno
		}
		if ( isset( $administradores ) ) {
			foreach( $administradores as $administrador ) {
				$telefono_propietario[]	= sms_q_procesa_el_telefono( $pedido, $administrador, $smsq_settings['servicio'], true );
			}
		} else {
			$telefono_propietario = sms_q_procesa_el_telefono( $pedido, $smsq_settings['telefono'], $smsq_settings['servicio'], true );	
		}
		
		//WPML
		if ( function_exists( 'icl_register_string' ) || !$wpml_activo ) { //Versión anterior a la 3.2
			$mensaje_pedido		= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_pedido', $smsq_settings['mensaje_pedido'] ) : $smsq_settings['mensaje_pedido'];
			$mensaje_recibido	= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_recibido', $smsq_settings['mensaje_recibido'] ) : $smsq_settings['mensaje_recibido'];
			$mensaje_procesando	= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_procesando', $smsq_settings['mensaje_procesando'] ) : $smsq_settings['mensaje_procesando'];
			$mensaje_completado	= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_completado', $smsq_settings['mensaje_completado'] ) : $smsq_settings['mensaje_completado'];
			$mensaje_canceledado	= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_canceledado', $smsq_settings['mensaje_canceledado'] ) : $smsq_settings['mensaje_canceledado'];
		} else if ( $wpml_activo ) { //Versión 3.2 o superior
			$mensaje_pedido		= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_pedido'], 'sms_q', 'mensaje_pedido' );
			$mensaje_recibido	= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_recibido'], 'sms_q', 'mensaje_recibido' );
			$mensaje_procesando	= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_procesando'], 'sms_q', 'mensaje_procesando' );
			$mensaje_completado	= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_completado'], 'sms_q', 'mensaje_completado' );
			$mensaje_canceledado	= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_canceledado'], 'sms_q', 'mensaje_canceledado' );
		}
		
		//Cargamos los proveedores SMS
		include_once( 'includes/admin/proveedores.php' );
		//Envía el SMS
		switch( $estado ) {
			case 'on-hold': //Pedido en espera
				if ( !!array_intersect( array( "todos", "mensaje_pedido" ), $smsq_settings['mensajes'] ) && isset( $smsq_settings['notificacion'] ) && $smsq_settings['notificacion'] == 1 && !$notificacion ) {
					if ( !is_array( $telefono_propietario ) ) {
						sms_q_envia_sms( $smsq_settings, $telefono_propietario, sms_q_procesa_variables( $mensaje_pedido, $pedido, $smsq_settings['variables'] ) ); //Mensaje para el propietario
					} else {
						foreach( $telefono_propietario as $administrador ) {
							sms_q_envia_sms( $smsq_settings, $administrador, sms_q_procesa_variables( $mensaje_pedido, $pedido, $smsq_settings['variables'] ) ); //Mensaje para los propietarios
						}
					}
				}
						
				if ( !!array_intersect( array( "todos", "mensaje_recibido" ), $smsq_settings['mensajes'] ) ) {
					//Limpia el temporizador para pedidos recibidos
					wp_clear_scheduled_hook( 'sms_q_ejecuta_el_temporizador' );

					$mensaje = sms_q_procesa_variables( $mensaje_recibido, $pedido, $smsq_settings['variables'] ); //Mensaje para el cliente

					//Temporizador para pedidos recibidos
					if ( isset( $smsq_settings['temporizador'] ) && $smsq_settings['temporizador'] > 0 ) {
						wp_schedule_single_event( time() + ( absint( $smsq_settings['temporizador'] ) * 60 * 60 ), 'sms_q_ejecuta_el_temporizador' );
					}
				}
				break;
			case 'processing': //Pedido procesando
				if ( !!array_intersect( array( "todos", "mensaje_pedido" ), $smsq_settings['mensajes'] ) && isset( $smsq_settings['notificacion'] ) && $smsq_settings['notificacion'] == 1 && $notificacion ) {
					if ( !is_array( $telefono_propietario ) ) {
						sms_q_envia_sms( $smsq_settings, $telefono_propietario, sms_q_procesa_variables( $mensaje_pedido, $pedido, $smsq_settings['variables'] ) ); //Mensaje para el propietario
					} else {
						foreach( $telefono_propietario as $administrador ) {
							sms_q_envia_sms( $smsq_settings, $administrador, sms_q_procesa_variables( $mensaje_pedido, $pedido, $smsq_settings['variables'] ) ); //Mensaje para los propietarios
						}
					}
				}
				if ( !!array_intersect( array( "todos", "mensaje_procesando" ), $smsq_settings['mensajes'] ) ) {
					$mensaje = sms_q_procesa_variables( $mensaje_procesando, $pedido, $smsq_settings['variables'] );
				}
				break;
			case 'completed': //Pedido completado
				if ( !!array_intersect( array( "todos", "mensaje_completado" ), $smsq_settings['mensajes'] ) ) {
					$mensaje = sms_q_procesa_variables( $mensaje_completado, $pedido, $smsq_settings['variables'] );
				}
				break;
			case 'cancelled': //Pedido completado
				if ( !!array_intersect( array( "todos", "mensaje_canceledado" ), $smsq_settings['mensajes'] ) ) {
					$mensaje = sms_q_procesa_variables( $mensaje_canceledado, $pedido, $smsq_settings['variables'] );
				}
				break;
			default: //Pedido con estado personalizado
				$mensaje = sms_q_procesa_variables( $smsq_settings[$estado], $pedido, $smsq_settings['variables'] );
		}

		if ( isset( $mensaje ) && ( !$internacional || ( isset( $smsq_settings['internacional'] ) && $smsq_settings['internacional'] == 1 ) ) && !$notificacion ) {
			if ( !is_array( $telefono ) ) {
				sms_q_envia_sms( $smsq_settings, $telefono, $mensaje ); //Mensaje para el teléfono de facturación
			} else {
				foreach( $telefono as $cliente ) {
					sms_q_envia_sms( $smsq_settings, $cliente, $mensaje ); //Mensaje para los teléfonos recibidos
				}
			}
			if ( $enviar_envio ) {
				sms_q_envia_sms( $smsq_settings, $telefono_envio, $mensaje ); //Mensaje para el teléfono de envío
			}
		}
	}
	add_action( 'woocommerce_order_status_pending_to_on-hold_notification', 'sms_q_procesa_estados', 10 ); //Funciona cuando el pedido es marcado como recibido
	add_action( 'woocommerce_order_status_failed_to_on-hold_notification', 'sms_q_procesa_estados', 10 );
	add_action( 'woocommerce_order_status_processing', 'sms_q_procesa_estados', 10 ); //Funciona cuando el pedido es marcado como procesando
	add_action( 'woocommerce_order_status_completed', 'sms_q_procesa_estados', 10 ); //Funciona cuando el pedido es marcado como completo
	add_action( 'woocommerce_order_status_cancelled', 'sms_q_procesa_estados', 10 ); //Funciona cuando el pedido es marcado como completo

	function sms_q_notificacion( $pedido ) {
		sms_q_procesa_estados( $pedido, true );
	}
	add_action( 'woocommerce_order_status_pending_to_processing_notification', 'sms_q_notificacion', 10 ); //Funciona cuando el pedido es marcado directamente como procesando
	
	//Temporizador
	function sms_q_temporizador() {
		global $smsq_settings;
		
		$pedidos = wc_get_orders( array(
			'limit'			=> -1,
			'date_created'	=> '<' . ( time() - ( absint( $smsq_settings['temporizador'] ) * 60 * 60 ) - 1 ),
			'status'		=> 'on-hold',
		) );

		if ( $pedidos ) {
			foreach ( $pedidos as $pedido ) {
				sms_q_procesa_estados( is_callable( array( $pedido, 'get_id' ) ) ? $pedido->get_id() : $pedido->id, false );
			}
		}
	}
	add_action( 'sms_q_ejecuta_el_temporizador', 'sms_q_temporizador' );

	//Envía las notas de cliente por SMS
	function sms_q_procesa_notas( $datos ) {
		global $smsq_settings, $wpml_activo;
		
		//Comprobamos si se tiene que enviar el mensaje
		if ( isset( $smsq_settings['mensajes']) && !array_intersect( array( "todos", "mensaje_nota" ), $smsq_settings['mensajes'] ) ) {
			return;
		}
	
		//Pedido
		$numero_de_pedido		= $datos['order_id'];
		$pedido					= new WC_Order( $numero_de_pedido );
		//Recoge datos del formulario de facturación
		$billing_country		= is_callable( array( $pedido, 'get_billing_country' ) ) ? $pedido->get_billing_country() : $pedido->billing_country;
		$billing_phone			= is_callable( array( $pedido, 'get_billing_phone' ) ) ? $pedido->get_billing_phone() : $pedido->billing_phone;
		$shipping_country		= is_callable( array( $pedido, 'get_shipping_country' ) ) ? $pedido->get_shipping_country() : $pedido->shipping_country;	
		$campo_envio			= get_post_meta( $numero_de_pedido, $smsq_settings['campo_envio'], false );
		$campo_envio			= ( isset( $campo_envio[0] ) ) ? $campo_envio[0] : '';
		$telefono				= sms_q_procesa_el_telefono( $pedido, $billing_phone, $smsq_settings['servicio'] );
		$telefono_envio			= sms_q_procesa_el_telefono( $pedido, $campo_envio, $smsq_settings['servicio'], false, true );
		$enviar_envio			= ( isset( $smsq_settings['envio'] ) && $telefono != $telefono_envio && $smsq_settings['envio'] == 1 ) ? true : false;
		$internacional			= ( $billing_country && ( WC()->countries->get_base_country() != $billing_country ) ) ? true : false;
		$internacional_envio	= ( $shipping_country && ( WC()->countries->get_base_country() != $shipping_country ) ) ? true : false;
		//Recoge datos del formulario de facturación
		$billing_country		= is_callable( array( $pedido, 'get_billing_country' ) ) ? $pedido->get_billing_country() : $pedido->billing_country;
		$billing_phone			= is_callable( array( $pedido, 'get_billing_phone' ) ) ? $pedido->get_billing_phone() : $pedido->billing_phone;
		$shipping_country		= is_callable( array( $pedido, 'get_shipping_country' ) ) ? $pedido->get_shipping_country() : $pedido->shipping_country;
		$campo_envio			= get_post_meta( $numero_de_pedido, $smsq_settings['campo_envio'], false );
		$campo_envio			= ( isset( $campo_envio[0] ) ) ? $campo_envio[0] : '';
		$telefono				= sms_q_procesa_el_telefono( $pedido, $billing_phone, $smsq_settings['servicio'] );
		$telefono_envio			= sms_q_procesa_el_telefono( $pedido, $campo_envio, $smsq_settings['servicio'], false, true );
		$enviar_envio			= ( $telefono != $telefono_envio && isset( $smsq_settings['envio'] ) && $smsq_settings['envio'] == 1 ) ? true : false;
		$internacional			= ( $billing_country && ( WC()->countries->get_base_country() != $billing_country ) ) ? true : false;
		$internacional_envio	= ( $shipping_country && ( WC()->countries->get_base_country() != $shipping_country ) ) ? true : false;

		//WPML
		if ( function_exists( 'icl_register_string' ) || !$wpml_activo ) { //Versión anterior a la 3.2
			$mensaje_nota		= ( $wpml_activo ) ? icl_translate( 'sms_q', 'mensaje_nota', $smsq_settings['mensaje_nota'] ) : $smsq_settings['mensaje_nota'];
		} else if ( $wpml_activo ) { //Versión 3.2 o superior
			$mensaje_nota		= apply_filters( 'wpml_translate_single_string', $smsq_settings['mensaje_nota'], 'sms_q', 'mensaje_nota' );
		}
		
		//Cargamos los proveedores SMS
		include_once( 'includes/admin/proveedores.php' );		
		//Envía el SMS
		if ( !$internacional || ( isset( $smsq_settings['internacional'] ) && $smsq_settings['internacional'] == 1 ) ) {
			if ( !is_array( $telefono ) ) {
				sms_q_envia_sms( $smsq_settings, $telefono, sms_q_procesa_variables( $mensaje_nota, $pedido, $smsq_settings['variables'], wptexturize( $datos['customer_note'] ) ) ); //Mensaje para el teléfono de facturación
			} else {
				foreach( $telefono as $cliente ) {
					sms_q_envia_sms( $smsq_settings, $cliente, sms_q_procesa_variables( $mensaje_nota, $pedido, $smsq_settings['variables'], wptexturize( $datos['customer_note'] ) ) ); //Mensaje para los teléfonos recibidos
				}
			}
			if ( $enviar_envio ) {
				sms_q_envia_sms( $smsq_settings, $telefono_envio, sms_q_procesa_variables( $mensaje_nota, $pedido, $smsq_settings['variables'], wptexturize( $datos['customer_note'] ) ) ); //Mensaje para el teléfono de envío
			}
		}
	}
	add_action( 'woocommerce_new_customer_note', 'sms_q_procesa_notas', 10 );
} else {
	add_action( 'admin_notices', 'sms_q_requiere_wc' );
}

//Muestra el mensaje de activación de WooCommerce y desactiva el plugin
function sms_q_requiere_wc() {
	global $sms_q;
		
	echo '<div class="error fade" id="message"><h3>' . $sms_q['plugin'] . '</h3><h4>' . __( "This plugin require WooCommerce active to run!", 'smsq-notifications-for-woocommerce' ) . '</h4></div>';
	deactivate_plugins( DIRECCION_sms_q );
}
