<?php
defined( 'ABSPATH' ) || exit;

function verdix_get_disclosure_types() {
	return array(
		'none'         => __( 'No disclosure', 'verdix-reviews' ),
		'affiliate'    => __( 'Affiliate links', 'verdix-reviews' ),
		'free_product' => __( 'Free product received', 'verdix-reviews' ),
		'sponsored'    => __( 'Sponsored', 'verdix-reviews' ),
		'custom'       => __( 'Custom text', 'verdix-reviews' ),
	);
}

function verdix_get_disclosure_presets() {
	$presets = array(
		'affiliate'    => __( 'This review contains affiliate links. We may earn a commission if you buy through them, at no extra cost to you.', 'verdix-reviews' ),
		'free_product' => __( 'We received this product free of charge for review. Our opinion is our own.', 'verdix-reviews' ),
		'sponsored'    => __( 'This review is sponsored. We were paid to publish it, and our opinion is our own.', 'verdix-reviews' ),
	);

	return apply_filters( 'verdix_disclosure_presets', $presets );
}

function verdix_get_disclosure_type( $review ) {
	$options = get_option( 'verdix_settings', array() );
	$types   = verdix_get_disclosure_types();

	if ( ! empty( $review['disclosure'] ) && isset( $types[ $review['disclosure'] ] ) ) {
		return $review['disclosure'];
	}

	if ( ! empty( $options['default_disclosure'] ) && isset( $types[ $options['default_disclosure'] ] ) ) {
		return $options['default_disclosure'];
	}

	return 'none';
}

function verdix_get_disclosure_text( $review ) {
	$type = verdix_get_disclosure_type( $review );

	if ( 'none' === $type ) {
		return '';
	}

	if ( 'custom' === $type ) {
		if ( ! empty( $review['disclosure_text'] ) ) {
			return $review['disclosure_text'];
		}
		$options = get_option( 'verdix_settings', array() );
		return ! empty( $options['default_disclosure_text'] ) ? $options['default_disclosure_text'] : '';
	}

	$presets = verdix_get_disclosure_presets();
	return isset( $presets[ $type ] ) ? $presets[ $type ] : '';
}

function verdix_katina_active() {
	return class_exists( 'KATINA_Post_Type' );
}

function verdix_sanitize_currency( $value ) {
	$code = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) $value ), 0, 3 ) );
	return 3 === strlen( $code ) ? $code : 'USD';
}

function verdix_sanitize_last_tested( $value ) {
	$value = sanitize_text_field( (string) $value );
	$date  = DateTime::createFromFormat( '!Y-m-d', $value );
	return ( $date && $date->format( 'Y-m-d' ) === $value ) ? $value : '';
}

function verdix_sanitize_offers( $offers ) {
	$clean = array();

	if ( ! is_array( $offers ) ) {
		return $clean;
	}

	foreach ( $offers as $offer ) {
		if ( ! is_array( $offer ) ) {
			continue;
		}

		$item = array(
			'merchant'    => isset( $offer['merchant'] ) ? sanitize_text_field( $offer['merchant'] ) : '',
			'price'       => isset( $offer['price'] ) ? sanitize_text_field( $offer['price'] ) : '',
			'currency'    => verdix_sanitize_currency( isset( $offer['currency'] ) ? $offer['currency'] : '' ),
			'url'         => isset( $offer['url'] ) ? esc_url_raw( $offer['url'] ) : '',
			'cta_text'    => isset( $offer['cta_text'] ) ? sanitize_text_field( $offer['cta_text'] ) : '',
			'katina_link' => isset( $offer['katina_link'] ) ? absint( $offer['katina_link'] ) : 0,
		);

		if ( '' === $item['merchant'] && '' === $item['price'] && '' === $item['url'] && ! $item['katina_link'] ) {
			continue;
		}

		$clean[] = $item;
	}

	return $clean;
}

function verdix_get_katina_url( $link_id ) {
	if ( ! $link_id || ! verdix_katina_active() || 'katina_link' !== get_post_type( $link_id ) || 'publish' !== get_post_status( $link_id ) ) {
		return '';
	}

	return KATINA_Post_Type::get_link_url( $link_id );
}

function verdix_get_offers( $review ) {
	$offers = ! empty( $review['offers'] ) && is_array( $review['offers'] ) ? $review['offers'] : array();

	if ( empty( $offers ) && ( ! empty( $review['price'] ) || ! empty( $review['cta_url'] ) ) ) {
		$offers = array(
			array(
				'merchant'    => '',
				'price'       => isset( $review['price'] ) ? $review['price'] : '',
				'currency'    => ! empty( $review['currency'] ) ? $review['currency'] : 'USD',
				'url'         => isset( $review['cta_url'] ) ? $review['cta_url'] : '',
				'cta_text'    => isset( $review['cta_text'] ) ? $review['cta_text'] : '',
				'katina_link' => 0,
			)
		);
	}

	foreach ( $offers as $i => $offer ) {
		$katina_url                = verdix_get_katina_url( isset( $offer['katina_link'] ) ? (int) $offer['katina_link'] : 0 );
		$offers[ $i ]['is_katina'] = '' !== $katina_url;
		$offers[ $i ]['href']      = $katina_url ? $katina_url : ( isset( $offer['url'] ) ? $offer['url'] : '' );
	}

	return $offers;
}

function verdix_offer_rel( $offer, $review ) {
	$type = verdix_get_disclosure_type( $review );

	if ( ! empty( $offer['is_katina'] ) || in_array( $type, array( 'affiliate', 'sponsored' ), true ) ) {
		return 'sponsored nofollow noopener noreferrer';
	}

	return 'nofollow noopener noreferrer';
}

function verdix_format_price( $price, $currency ) {
	return trim( $currency . ' ' . $price );
}
