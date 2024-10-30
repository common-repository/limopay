<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       bdtask.com
 * @since      1.0.0
 *
 * @package    Limopay
 * @subpackage Limopay/admin/partials
 */
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'limopay_init_gateway_class' );
function limopay_init_gateway_class() {
 
	class WC_Limopay_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
	    public function __construct() {
		 
			$this->id                 = 'bdtask'; // payment gateway plugin ID
			$this->icon               = ''; 
			$this->has_fields         = true; // in case you need a custom credit card form
			$this->method_title       = esc_html('Limopay Gateway','limopay');
			$this->method_description = esc_html('Description of Limopay payment gateway','limopay'); // will be displayed on the options page

			$this->supports = array(
				'products'
			);
			// Method with all the options fields
			$this->init_form_fields();
			// Load the settings.
			$this->init_settings();
			$this->title           =   esc_html($this->get_option( 'title' ),'limopay');
			$this->receiverName    =   esc_html($this->get_option( 'Receiver UserName' ),'limopay');
			$this->description     =   esc_html($this->get_option( 'description' ),'limopay');
			$this->enabled         =   esc_html($this->get_option( 'enabled' ),'limopay');
			$this->testmode = 'yes'=== esc_html($this->get_option( 'testmode' ),'limopay');
			$this->private_key     =   esc_html($this->get_option( 'private_key' ),'limopay');
			$this->publishable_key =   esc_html($this->get_option( 'publishable_key' ),'limopay');
			$this->shop_id         =   esc_html($this->get_option('shop_id'),'limopay');
			$this->x_api_key       =   esc_html($this->get_option('x_api_key'),'limopay');
			// This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			// We need custom JavaScript to obtain a token
			add_action( 'wp_enqueue_scripts', array( $this, 'limopay_payment_scripts' ) );
			// You can also register a webhook here
			 add_action( 'woocommerce_api_paypal', array( $this, 'webhook' ) );
		 }
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
         public function init_form_fields(){
 
			$this->form_fields = array(
				'enabled' => array(
					'title'       => esc_html('Enable/Disable'),
					'label'       => esc_html('Enable  Gateway','limopay'),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => esc_html('no','limopay')
				),
				'title' => array(
					'title'       => esc_html('Title','limopay'),
					'type'        => 'text',
					'description' => esc_html('This controls the title which the user sees during checkout.','limopay'),
					'default'     => esc_html('Limopay','limopay'),
				),
				'description' => array(
					'title'       => esc_html('Description','limopay'),
					'type'        => 'textarea',
					'description' => esc_html('This controls the description which the user sees during checkout.','limopay'),
					'default'     => esc_html('Make payments and manage money in a secure way','limopay'),
				),

				'shop_id' => array(
					'title'       => esc_html('Shop Id','limopay'),
					'type'        => 'text',

				),
				'receiverName' => array(
					'title'       => esc_html('Receiver UserName','limopay'),
					'type'        => 'text'
				),
				'publishable_key' => array(
					'title'       => esc_html('Public Key','limopay'),
					'type'        => 'text'
				),
				'private_key' => array(
					'title'       => esc_html('Secret Key','limopay'),
					'type'        => 'password'
				),
				'x_api_key' => array(
					'title'       => esc_html('x-api-key','limopay'),
					'type'        => 'password'
				)
			);
		}
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		 public function payment_fields() {
		 
			// ok, let's display some description before the payment form
			if ( $this->description ) {
				// you can instructions for test mode, I mean test card numbers etc.
				if ( $this->testmode ) {
					$this->description .= esc_html(' Limopay Payment gateway','limopay');
					$this->description  = trim( esc_html($this->description),'limopay');
				}
				// display the description with <p> tags etc.
				echo wpautop( wp_kses_post(esc_html($this->description ) ) );
			}

			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
		 
			// Add this action hook if you want your custom payment gateway to support it
			do_action( 'woocommerce_credit_card_form_start', $this->id );
		 
			// I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
			echo '<div class="form-row"><label>'.esc_html("User Name","limopay").'<span class="required">*</span></label>
				<input id="" type="text" name="senderName" autocomplete="off">
				</div>
				
				<div class="form-row">
					<label>'.esc_html('OTP','limopay').'<span class="required">*</span></label>
					<input id="" type="text" name="otp" autocomplete="off">
				</div>
				<div class="clear"></div>';
		 
			do_action( 'woocommerce_credit_card_form_end', $this->id );
		 
			echo '<div class="clear"></div></fieldset>';

		 
		}
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function limopay_payment_scripts() {
 
			// we need JavaScript to process a token only on cart/checkout pages, right?
			if ( ! is_cart() && ! is_checkout() && ! isset($_GET['pay_for_order'])) {
				return;
			}
			// if our payment gateway is disabled, we do not have to enqueue JS too
			if ( esc_html('no','limopay') === $this->enabled ) {
				return;
			}
			// no reason to enqueue JavaScript if API keys are not set
			if ( empty( sanitize_text_field($this->private_key) ) || empty(sanitize_text_field($this->publishable_key )) ) {
				return;
			}
			// do not work with card detailes without SSL unless your website is in a test mode
			if ( ! $this->testmode && ! is_ssl() ) {
				return;
			}
		
			// in most payment processors you have to use PUBLIC KEY to obtain a token
			wp_localize_script( 'woocommerce_limopay', 'bdtask_params', array(
				'publishableKey' => $this->publishable_key
			) );
			wp_enqueue_script( 'woocommerce_limopay' );
		 
		}
		 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
		       global $woocommerce;
               $senderName=sanitize_text_field($_REQUEST['senderName']);
		       $otp		  =sanitize_text_field($_REQUEST['otp']);
	     
				if( empty( sanitize_text_field($_POST[ 'billing_first_name' ])) ) {
					wc_add_notice(esc_html('First name is required!','limopay'),esc_html('error','limopay') );
					return false;
				}
				if( empty( sanitize_text_field($_POST[ 'senderName' ])) ) {
					wc_add_notice(esc_html('Limopay payment username is required','limopay'), esc_html('error','limopay') );
					return false;
				}
				if( empty( $_POST[ 'otp' ]) ) {
					wc_add_notice( esc_html('Limopay Git PASS OTP is required','limopay'), esc_html('error','limopay') );
					return false;
				}
				return true;
 
		}
 
			/*
			 * We're processing the payments here, everything about it is in Step 5
			 */

         public function process_payment( $order_id ) {
                global $woocommerce;
                $order    = new WC_Order( sanitize_text_field($order_id) );
			    $amount   = sanitize_text_field($order->total);
				$currency = sanitize_text_field($this->get_option('currency'));
				$invoice  = sanitize_text_field($order->id);
				$email    = sanitize_text_field($order->bill_email);
				$tax      = sanitize_text_field($order->tax);
				$discount = sanitize_text_field($order->discount);
	           
		        $argsd = array(
	          	  'amount'  	 => sanitize_text_field($order->get_total()), 
				  'orderID' 	 => sanitize_text_field($order->get_order_number()),
				  'name'    	 => sanitize_text_field($order->get_billing_first_name()),
				  'lastname'	 => sanitize_text_field($order->get_billing_last_name()),
				  'address1'	 => sanitize_text_field($order->get_billing_address_1()),
				  'address2'	 => sanitize_text_field($order->get_billing_address_2()),
				  'postcode'	 => sanitize_text_field($order->get_billing_postcode()),
				  'country' 	 => sanitize_text_field($order->get_billing_country()),
				  'cart_content' => sanitize_text_field($order->get_items())
				);

	             $subtotal  = json_decode(sanitize_text_field($order->subtotal));
	             $shipping  = json_decode(sanitize_text_field($order->shipping_total));
	             $total_tax = json_decode(sanitize_text_field($order->total_tax));
       
                 $item=[];
             foreach( WC()->cart->get_cart_contents() as $key=>$cart_item ) {
			   $name=sanitize_text_field($cart_item['data']->name);
			   $id  =sanitize_text_field($cart_item['data']->id);

			   $price     = sanitize_text_field($cart_item['data']->price);
			   $quantity  =sanitize_text_field(($cart_item['quantity']));
			   $line_total=sanitize_text_field($cart_item['line_total']);

				$item[]= array(
	                'itemID'        => sanitize_text_field($id),
	                'title'         => sanitize_text_field($name),
	                'quantity'      => sanitize_text_field($cart_item['quantity']),
	                'BasePrice'     => sanitize_text_field($price),
	                'totalPrice'    => sanitize_text_field($cart_item['quantity']*$price)
	            );
		      }
		       $shop_id      = sanitize_text_field($this->get_option('shop_id'));
		       $publicKey    = sanitize_text_field($this->get_option('publishable_key'));
		       $secretKey    = sanitize_text_field($this->get_option('private_key'));
		       $x_api_key    = sanitize_text_field($this->get_option('x_api_key'));
		       $receiverName = sanitize_text_field($this->get_option('receiverName'));

			 $args = array(
				    'shopID'           => sanitize_text_field($shop_id),
				    'publicKey'        => sanitize_text_field($publicKey),
				    'secretKey'        => sanitize_text_field($secretKey),
				    'receiverName'     => sanitize_text_field($receiverName),
				    'senderName'       => sanitize_text_field($_POST['senderName']),
				    'otp'              => sanitize_text_field($_POST['otp']),
				    'billingDate'      => sanitize_text_field('2020-11-14 15:37:25.729963'),
                    'billingNote'      => esc_html('Lorem Ipsom...','limopay'),
				    'curtItem'         => sanitize_text_field($item),
				    'invoiceAmount'    => sanitize_text_field($subtotal),
				    'deliveryCharge'   => sanitize_text_field($shipping),
				    'vatTax'           => sanitize_text_field($total_tax),
				    'extraCharge'      => sanitize_text_field('0'),
				    'totalAmount'      => urlencode($amount),
				    
				    );

                $fields_string = json_encode($args);
                
				$dddd = array(
				    'body'        => sanitize_text_field($fields_string),
				    'timeout'     => '5',
				    'redirection' => '5',
				    'httpversion' => '1.0',
				    'blocking'    => true,
				    'headers'     => array(
				    	'header'    =>  "Content-Type: application/json",
		                'x-api-key' =>sanitize_text_field($x_api_key)
		                 ),
				    'cookies'     => array(),
				);
                $response = wp_remote_post( 'https://api.limopay.net/payment', $dddd);


                if(!empty(sanitize_text_field($shop_id)) || !empty(sanitize_text_field($publicKey)) || !empty(sanitize_text_field($secretKey)) || !empty(sanitize_text_field($receiverName))  || !empty(sanitize_text_field($x_api_key))){
	             if( !is_wp_error($response ) ) {
	             	       
				        $body = json_decode(sanitize_text_field($response['body']), true );
				      
					   if(sanitize_text_field($body['body']['response']['status'])==esc_html('success','limopay')){
					      WC()->cart->empty_cart();
					      $woocommerce->cart->empty_cart();
					      $order->payment_complete($this->id);
					      $order->reduce_order_stock();
						// some notes to customer (replace true with false to make it private)
						$order->add_order_note( esc_html('Hey, your order is paid! Thank you!','limopay'), true );
		            	return array(
						'result' => esc_html('success','limopay'),
						'redirect' => $this->get_return_url( $order )
						);
		               }else{
	                       wc_add_notice(esc_html($body['body']['response']['msg'],'limopay'), esc_html('error','limopay' ));
						  return;
		               }
			     }else{
			      
                    wc_add_notice(esc_html('Connection error.','limopay'),esc_html('error','limopay'));
				   return;
			     }

                }else{
                 wc_add_notice( esc_html('Payment Settings Shop Information is empty.','limopay'),esc_html('error','limopay'));
              }


    }
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
         
			$order = wc_get_order(sanitize_text_field($_GET['id']));
			$order->payment_complete();
			$order->reduce_order_stock();
		 
			update_option('webhook_debug', sanitize_text_field($_GET));
 
	 	}

 	}
}
