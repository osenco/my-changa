<?php
/**
 * @link              https://mauko.co.ke
 * @since             1.0.0
 * @package           my_changa
 *
 * @wordpress-plugin
 * Plugin Name:       My Changa Donations
 * Plugin URI:        https://osen.co/products/categories/wp/my-changa
 * Description:       Receive contributions/donations via Safaricom MyMPesa
 * Version:           1.0.0
 * Author:            Mauko Maunde
 * Author URI:        https://mauko.co.ke
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       my-changa
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'MY_CHANGA_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * @todo Create default configuration - mc-options
 */
register_activation_hook( __FILE__, 'activate_my_changa' );
function activate_my_changa() {
}

/**
 * Delete mc-options setting during plugin deactivation.
 */
register_deactivation_hook( __FILE__, 'deactivate_my_changa' );
function deactivate_my_changa() {
}

/**
 * Include external files
 */
require plugin_dir_path( __FILE__ ) . 'includes/mpesa.php';
require plugin_dir_path( __FILE__ ) . 'includes/settings.php';

add_action('admin_menu', 'mc_options_page');
function mc_options_page()
{
  add_menu_page(
    'My Changa Settings',
    'My Changa',
    'manage_options',
    'mc',
    'mc_options_page_html',
    'dashicons-smiley',
    20
  );
}

add_filter( 'plugin_row_meta', 'mc_row_meta', 10, 2 );
function mc_row_meta( $links, $file )
{
  $plugin = plugin_basename( __FILE__ );

  if ( $plugin == $file ) {
    $row_meta = array( 
      'github'    => '<a href="' . esc_url( 'https://github.com/wcmpesa/my-changa' ) . '" target="_blank" aria-label="' . esc_attr__( 'Contribute on Github', 'woocommerce' ) . '">' . esc_html__( 'Github', 'woocommerce' ) . '</a>',
      'apidocs' => '<a href="' . esc_url( 'https://developer.safaricom.co.ke/docs/' ) . '" target="_blank" aria-label="' . esc_attr__( 'MPesa API Docs ( Daraja )', 'woocommerce' ) . '">' . esc_html__( 'API docs', 'woocommerce' ) . '</a>'
     );

    return array_merge( $links, $row_meta );
  }

  return ( array ) $links;
}

add_filter( 'plugin_action_links_'.plugin_basename( __FILE__ ), 'mc_action_links' );
function mc_action_links( $links )
{
  return array_merge( $links, [ '<a href="'.admin_url( 'admin.php?page=mc' ).'">&nbsp;Preferences</a>' ] );
}

$mconfig = get_option( 'mc_options' );
$mconfig['mc_callback_url']     = rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=reconcile';
$mconfig['mc_timeout_url']      = rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=timeout';
$mconfig['mc_result_url'] 		  = rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=reconcile';
$mconfig['mc_confirmation_url'] = rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=confirm';
$mconfig['mc_validation_url'] 	= rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=validate';
mc_mpesa_setup( $mconfig );

add_shortcode('MCFORM', 'mc_form_callback');
function mc_form_callback( $atts = array(), $content = null ) {
	$mconfig = get_option( 'mc_options' );

	$status = isset( $_SESSION['mc_trx_status'] ) ? $mconfig['mc_mpesa_conf_msg'].'<br>'.$_SESSION['mc_trx_status'] : '';
  return '<form id="mc-contribution-form" method="POST" action="" class="mc_contribution_form">
  	<p>'.$status.'</p>
  	<input type="hidden" name="action" value="process_mc_form">
    
  	<label for="mc-phone">Phone Number</label>
  	<input id="mc-phone" type="text" name="mc-phone" placeholder="Phone Number" class="mc_phone"><br>

  	<label for="mc-amount">Amount to contribute</label>
  	<input id="mc-amount" type="text" name="mc-amount" value="500" class="mc_amount"><br>

  	<button type="submit" name="mc-contribute" class="mc_contribute">CONTRIBUTE</button>
  </form>';
}

add_action( 'init', 'mc_process_form_data' );
function mc_process_form_data() {
  if ( isset( $_POST['mc-contribute'] ) ) {
    $amount   = trim( $_POST['mc-amount'] );
  	$phone 		= trim( $_POST['mc-phone'] );

  	$response 	= mc_mpesa_checkout( $amount, $phone, 'Contributions' );
  	$status 	= json_decode( $response );

    if( !$response ){
      $s .= "<b>Failed!</b> Could not process contribution. Please try again";
    } elseif ( isset( $status->errorCode ) ) {
      $s .= "<b>Request ID:</b> {$status->requestId}<br>";
      $s .= "<b>Error Code:</b> {$status->errorCode}<br>";
      $s .= "<b>Error Message:</b> {$status->errorMessage} {$phone}<br>";
    } else {
      $ss = $status->Body->stkCallback;
      $s .= "<b>Request ID:</b> {$ss->MerchantRequestID}<br>";
      $s .= "<b>Checkout ID:</b> {$ss->CheckoutRequestID}<br>";
      $s .= "<b>Code:</b> {$ss->ResultCode}<br>";
      $s .= "<b>Description:</b> {$ss->ResultDesc}";
    }

    $_SESSION['mc_trx_status'] = $s;
  }
}
/**
 * Register Validation and Confirmation URLs
 * Outputs registration status
 */
add_action( 'init', 'mc_mpesa_do_register' );
function mc_mpesa_do_register()
{
	header( "Access-Control-Allow-Origin: *" );
	header( 'Content-Type:Application/json' );
	if ( ! isset( $_GET['mpesa_ipn_register'] ) ){ return; }
    
	wp_send_json( mc_mpesa_register_urls() );
}

/**
 * 
 */
add_action( 'init', 'mc_mpesa_confirm' );
function mc_mpesa_confirm()
{
	if ( ! isset( $_GET['mpesa_ipn_listener'] ) ) return;
    if ( $_GET['mpesa_ipn_listener'] !== 'confirm' ) return;

	$response = json_decode( file_get_contents( 'php://input' ), true );

	if( ! isset( $response['Body'] ) ){
    	return;
    }

	header( "Access-Control-Allow-Origin: *" );
	header( 'Content-Type:Application/json' );

	wp_send_json(
		array(
		  'ResponseCode'  => 0, 
		  'ResponseDesc'  => 'Success',
		  'ThirdPartyTransID'	=> 0
		)
	);
}

/**
 * 
 */
add_action( 'init', 'mc_mpesa_validate' );
function mc_mpesa_validate()
{
	if ( ! isset( $_GET['mpesa_ipn_listener'] ) ){ return; }
    if ( $_GET['mpesa_ipn_listener'] !== 'validate' ){ return; }

	$response = json_decode( file_get_contents( 'php://input' ), true );

	if( ! isset( $response['Body'] ) ){
    	return;
    }

	header( "Access-Control-Allow-Origin: *" );
	header( 'Content-Type:Application/json' );

	wp_send_json(
		array(
		  'ResponseCode'  => 0, 
		  'ResponseDesc'  => 'Success',
		  'ThirdPartyTransID'	=> 0
		)
	);
}
