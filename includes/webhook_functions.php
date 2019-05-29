<?php

/**
 * Webhook request.
 *
 */
function ecurring_webhook( $request ) {

	if ( isset( $_GET['ecurring-webhook'] ) ) {

		global $wpdb;

		foreach ( $_GET as $key => $value ) {
			eCurring_WC_Plugin::debug( __FUNCTION__ . ' - eCurring webhook call for ' . sanitize_text_field($value) );
		}

		$response = file_get_contents( 'php://input' );

		$ecurring_webhook = sanitize_text_field($_GET['ecurring-webhook']);

			// We only handle transaction webhook calls at the moment
			if ( $ecurring_webhook == 'transaction' ) {
			eCurring_WC_Plugin::debug( __FUNCTION__ . " -  Transaction webhook response: " . sanitize_text_field($response) );

			$api             = eCurring_WC_Plugin::getApiHelper();
			$data            = eCurring_WC_Plugin::getDataHelper();
			$subscription_id = sanitize_text_field(json_decode( $response, true )['subscription_id']);
			$transaction_id  = sanitize_text_field(json_decode( $response, true )['transaction_id']);
			$transaction     = json_decode( $api->apiCall( 'GET', 'https://api.ecurring.com/transactions/' . $transaction_id ), true );

			$transaction_attrs  = $transaction['data']['attributes'];
			$transaction_status = $transaction_attrs['status'];
			eCurring_WC_Plugin::debug( __FUNCTION__ . " - Transaction " . $transaction_id . ":" );
			eCurring_WC_Plugin::debug( $transaction );

			// Get related subscription to this transaction
			$prepare = $wpdb->prepare( "SELECT post_id FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_ecurring_subscription_id' AND meta_value = '%d'",$subscription_id);
			$subscription_order_id = $wpdb->get_var( $prepare );
			$subscription_order = wc_get_order( $subscription_order_id );

			if (!$subscription_order) {
				eCurring_WC_Plugin::debug("ERROR: subscription_order related to this transaction (".$transaction_id.") not found.");
				return $request;
			}

			$address     = $subscription_order->get_address();
			$customer_id = $subscription_order->get_user_id();

			foreach ( $subscription_order->get_items() as $item_id => $item_data ) {
				// Get product ID from subscription order
				$product    = $item_data->get_product();
				$product_id = $product->get_id();
				eCurring_WC_Plugin::debug( 'Product ID: ' . $product_id );
			}

			$status = '';

			// Convert eCurring transaction statuses to WC statuses
			switch ( $transaction_status ) {
				case 'succeeded':
				case 'rescheduled':
					$status = 'pending';
					break;
				case 'fulfilled':
					$status = 'processing';
					break;
				case 'charged_back':
				case 'payment_reminder_overdue':
				case 'payment_failed':
				case 'failed':
					$status = 'failed';
					break;
				case 'payment_reminder_scheduled':
				case 'payment_reminder_sent':
					$status = 'wc-ecurring-retry';
					break;
			}

			// Check if this transaction already exist so this webhook call is an update of it's status
			$prepare = $wpdb->prepare( "SELECT post_id FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_transaction_id' AND meta_value = '%s'",$transaction_id);
			$transaction_order_id = $wpdb->get_var( $prepare );

			// Renewal/First payment update
			if ( ! empty( $transaction_order_id ) ) {

				$payment_type = get_post_meta($transaction_order_id,'_first_transaction_completed',true) ? 'First' : 'Renewal';

				eCurring_WC_Plugin::debug( 'Update of: #' . $transaction_order_id );

				$transaction_order = wc_get_order( $transaction_order_id );
				$old_status        = $transaction_order->get_status();

					// Update status of renewal payment if its changed
					if ( !empty($status) && ($old_status != $status) ) {

						$transaction_order->update_status( $status );
						$transaction_order->add_order_note( sprintf(
							__( 'eCurring transaction status has been updated to %s, WooCommerce order status updated to %s.', 'woo-ecurring' ),
							$data->geteCurringPrettyStatus( $transaction_status),
							wc_get_order_status_name($status)
						) );
						$transaction_order->save();

						eCurring_WC_Plugin::debug( $payment_type.' order #' . $transaction_order_id . ' updated. Old WooCommerce status: ' . $old_status . '. New WooCommerce status: ' . $status );
					}

			} else {

				$is_first_payment = !get_post_meta($subscription_order_id,'_first_transaction_completed',true);

				// Updating subscription's first order with transaction data
				if ($is_first_payment) {
					$order = wc_get_order( $subscription_order_id );

					update_post_meta( $subscription_order_id, '_transaction_id', $transaction_id );
					$order->update_meta_data( '_first_transaction_completed', '1' );

					$order->add_order_note( sprintf(
						__( 'First transaction %s has been received.', 'woo-ecurring' ),
						'<a href="https://app.ecurring.com/transactions/' . $transaction_id . '" target="_blank">' . $transaction_id . '</a>'
					) );

					$order->update_status( $status );
					$order->save();

					eCurring_WC_Plugin::debug( 'First transaction ' . $transaction_id . ' has been received with eCurring status ' . $data->geteCurringPrettyStatus( $transaction_status ) );

				// Create renewal payment
				} else {
					$order_data = array (
						'status'      => $status,
						'customer_id' => $customer_id,
					);
					$order      = wc_create_order( $order_data );
					if ( isset( $product ) ) {
						$order->add_product( $product, 1 );
					}
					$order->set_address( $address, 'billing' );
					$order->calculate_totals();

					$order->set_parent_id( $subscription_order_id );
					$order->save();

					eCurring_WC_Plugin::debug( 'Order #' . $order->get_id() . ' saved.' );

					$payment_method       = get_post_meta( $subscription_order_id, '_payment_method', true );
					$payment_method_title = get_post_meta( $subscription_order_id, '_payment_method_title', true );

					// Add meta data to renewal payment
					update_post_meta($order->get_id(),'_transaction_id',$transaction_id);
					update_post_meta($order->get_id(),'_ecurring_transaction_id',$transaction_id);
					update_post_meta($order->get_id(),'_ecurring_subscription_id',$subscription_id);
					update_post_meta($order->get_id(),'_payment_method',$payment_method);
					update_post_meta($order->get_id(),'_payment_method_title',$payment_method_title);

					eCurring_WC_Plugin::debug( 'Created renewal transaction order #' . $order->get_id() . ' with eCurring status ' . $data->geteCurringPrettyStatus( $transaction_status) );

					$order->add_order_note( sprintf(
						__( 'Created renewal order with WooCommerce status %s - for transaction %s with eCurring status %s', 'woo-ecurring' ),
						$order->get_status(),
						'<a href="https://app.ecurring.com/transactions/'.$transaction_id.'" target="_blank">'.$transaction_id.'</a>',
						$data->geteCurringPrettyStatus( $transaction_status)
					) );
				}
			}
		}
	}

	return $request;
}

add_filter( 'request', 'ecurring_webhook' );