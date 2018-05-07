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
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       my-changa
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MY_CHANGA_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 */
register_activation_hook( __FILE__, 'activate_my_changa' );
function activate_my_changa() {
}

/**
 * The code that runs during plugin deactivation.
 */
register_deactivation_hook( __FILE__, 'deactivate_my_changa' );
function deactivate_my_changa() {
}

/**
 * The main MyMPesa file
 */
require plugin_dir_path( __FILE__ ) . 'includes/mpesa.php';
require plugin_dir_path( __FILE__ ) . 'includes/settings.php';

add_action('admin_menu', 'mc_options_page');
function mc_options_page()
{
    add_menu_page(
        'My Changa Options',
        'My Changa',
        'manage_options',
        'mc',
        'mc_options_page_html',
        'dashicons-smiley',
        20
    );
}

$mconfig = get_option( 'mc_options' );
$mconfig['callback_url'] 		= rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=reconcile';
$mconfig['timeout_url'] 		= rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=timeout';
$mconfig['result_url'] 		= rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=reconcile';
$mconfig['confirmation_url'] 	= rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=confirm';
$mconfig['validation_url'] 	= rtrim( home_url(), '/').':'.$_SERVER['SERVER_PORT'].'/?mpesa_ipn_listener=validate';

mc_mpesa_setup( $mconfig );

add_shortcode('MCFORM', 'mc_form_callback');
function mc_form_callback( $atts = array(), $content = null ) {
	$mconfig = get_option( 'mc_options' );

	$status = isset( $_SESSION['mc_trx_status'] ) ? $mconfig['mc_mpesa_conf_msg'].'<br>'.$_SESSION['mc_trx_status'] : '';
    $output = '<form method="POST" action="" class="mc_contribution_form">
    	<p>'.$status.'</p>
    	<input type="hidden" name="action" value="process_mc_form">
    	<label for="mc-phone">Phone Number</label>
    	<input id="mc-phone" type="text" name="phone" placeholder="Phone Number" class="mc_phone"><br>

    	<label for="mc-amount">Amount to contribute</label>
    	<input id="mc-amount" type="text" name="amount" value="500" class="mc_amount"><br>

    	<button type="submit" name="mc-contribute" class="mc_contribute">CONTRIBUTE</button>
    </form>';

    return $output;

}

add_action( 'init', 'mc_process_form_data' );
function mc_process_form_data() {
  if ( isset( $_POST['mc-contribute'] ) ) {
  	$phone 		= trim( $_POST['mc-phone'] );
  	$amount 	= trim( $_POST['mc-amount'] );
  	
  	$response 	= mc_mpesa_checkout( $amount, $phone, 'Contributions' );
  	$status 	= json_decode( $response );
  	$_SESSION['mc_trx_status'] = "<b>Request ID:</b> $status->requestId";
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
