<?php
//Envía el mensaje SMS
function sms_q_envia_sms( $smsq_settings, $telefono, $mensaje ) {
	switch ( $smsq_settings['servicio'] ) {
		case "smsq_esms":	
			$respuesta = wp_remote_get( "https://api.smsq.global/api/v2/SendSMS?ApiKey=" . $smsq_settings['clave_smsq_esms'] . "&ClientId=" . $smsq_settings['apitoken_smsq'] . "&SenderId=" . $smsq_settings['identificador_smsq_esms'] . "&Is_Unicode=true&MobileNumbers=" . $telefono . "&Message=" . sms_q_codifica_el_mensaje( $mensaje ) );
			break;
	}

	if ( isset( $smsq_settings['debug'] ) && $smsq_settings['debug'] == "1" && isset( $smsq_settings['campo_debug'] ) ) {
		$correo	= __( 'Mobile number:', 'smsq-notifications-for-woocommerce' ) . "\r\n" . $telefono . "\r\n\r\n";
		$correo	.= __( 'Message: ', 'smsq-notifications-for-woocommerce' ) . "\r\n" . $mensaje . "\r\n\r\n"; 
		$correo	.= __( 'Gateway answer: ', 'smsq-notifications-for-woocommerce' ) . "\r\n" . print_r( $respuesta, true );
		wp_mail( $smsq_settings['campo_debug'], 'WC - SMS Q Notifications', $correo, 'charset=UTF-8' . "\r\n" ); 
	}
}