<?php
/**
 * @package Future MyMPesa PHP SDK
 * @version 1.0
 * @author Mauko Maunde < hi@mauko.co.ke >
 * @link https://code.mauko.co.ke/mpesa/php-sdk/
 */

function mc_mpesa_setup( $config )
{
    $GLOBALS['mc_mpesa_env']               = $config['env'];
    $GLOBALS['mc_mpesa_name']              = $config['name'];
    $GLOBALS['mc_mpesa_shortcode']         = $config['shortcode'];
    $GLOBALS['mc_mpesa_type']              = $config['type'];
    $GLOBALS['mc_mpesa_key']               = $config['key'];
    $GLOBALS['mc_mpesa_secret']            = $config['secret'];
    $GLOBALS['mc_mpesa_username']          = $config['username'];
    $GLOBALS['mc_mpesa_password']          = $config['password'];
    $GLOBALS['mc_mpesa_passkey']           = $config['passkey'];
    $GLOBALS['mc_mpesa_callback_url']      = $config['callback_url'];
    $GLOBALS['mc_mpesa_timeout_url']       = $config['timeout_url'];
    $GLOBALS['mc_mpesa_result_url']        = $config['result_url'];
    $GLOBALS['mc_mpesa_confirmation_url']  = $config['confirmation_url'];
    $GLOBALS['mc_mpesa_validation_url']    = $config['validation_url'];

    $GLOBALS['mc_mpesa_country']           = isset( $config['country'] ) ? $config['country'] : 254;

    /**
     * Main MyMPesa class 
     */
    class MyMPesa
    {
        private function __construct(){}

        public static function authenticate()
        {
            $endpoint = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

            $credentials = base64_encode( "{$GLOBALS['mc_mpesa_key']}:{$GLOBALS['mc_mpesa_secret']}" );

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Basic '.$credentials ) );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
            $response = curl_exec( $curl );
            
            return json_decode( $response )->access_token;
        }

        /**
         * Register confirmation and validation endpoints
         */
        public function register()
        {
            $token = self::authenticate();

            $endpoint = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl' : 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl';

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array(
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                )
            );

            $curl_post_data = array( 
                'ShortCode'         => $GLOBALS['mc_mpesa_shortcode'],
                'ResponseType'      => 'Cancelled',
                'ConfirmationURL'   => $GLOBALS['mc_mpesa_confirmation_url'],
                'ValidationURL'     => $GLOBALS['mc_mpesa_validation_url']
            );

            $data_string = json_encode( $curl_post_data );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );

            $response = curl_exec( $curl );

            header("Content-Type:application/json");
            echo $response;
        }


        /**
         * Transaction Reversal API
         * Use this function to initiate a reversal request
         * @param $TransactionID - Organization Receiving the funds
         * @param $Amount - Amount involved
         * @param $ReceiverParty - Organization /MSISDN sending the transaction
         * @param $RecieverIdentifierType - Type of organization receiving the transaction
         * @param $Occasion -   Optional Parameter
         * @param $Remarks - Comments that are sent along with the transaction.
         * @return mixed-string
         */
        public static function reverse( $args )
        {
            $TransactionID          = $args['id'];
            $Amount                 = $args['amount']; 
            $ReceiverParty          = $args['receiver']; 
            $RecieverIdentifierType = $args['reciever_type']; 
            $Occasion               = isset( $args['occassion'] ) ? $args['occassion'] : "TransactionReversal"; 
            $Remarks                = isset( $args['remarks'] ) ? $args['remarks'] : "Transaction Reversal";

            $token                  = self::authenticate();

            $endpoint               = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/reversal/v1/request' : 'https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request';

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array( 
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                ) 
            );

            $curl_post_data = array( 
                'CommandID'               => 'TransactionReversal',
                'Initiator'               => $GLOBALS['mc_mpesa_business'],
                'SecurityCredential'      => $GLOBALS['mc_mpesa_credentials'],
                'TransactionID'           => $TransactionID,
                'Amount'                  => $Amount,
                'ReceiverParty'           => $ReceiverParty,
                'RecieverIdentifierType'  => $RecieverIdentifierType,
                'ResultURL'               => $GLOBALS['mc_mpesa_result_url'],
                'QueueTimeOutURL'         => $GLOBALS['mc_mpesa_timeout_url'],
                'Remarks'                 => $Remarks,
                'Occasion'                => $Occasion
            );

            $data_string = json_encode( $curl_post_data );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            return $response = curl_exec( $curl );
        }

        /**
         * B2C API
         * @param $CommandID - Unique command for each transaction - SalaryPayment | BusinessPayment | PromotionPayment
         * @param $Amount - The amount being transacted
         * @param $PartyB - Phone number receiving the transaction
         * @param $Remarks - Comments that are sent along with the transaction.
         * @param $Occasion -   Optional
         * @return string
         */
        public static function b2c( $args )
        {
            $Amount     = $args['amount']; 
            $PartyB     = $args['receiver']; 
            $CommandID  = isset( $args['command'] ) ? $args['command'] : ""; 
            $Occasion   = isset( $args['occassion'] ) ? $args['occassion'] : ""; 
            $Remarks    = isset( $args['remarks'] ) ? $args['remarks'] : "";
            
            $token      = self::authenticate();
          
            $endpoint   = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest' : 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';

            $curl       = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array( 
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                ) 
            );

            $curl_post_data = array( 
                'InitiatorName'       => $GLOBALS['mc_mpesa_username'],
                'SecurityCredential'  => $GLOBALS['mc_mpesa_credentials'],
                'CommandID'           => $CommandID ,
                'Amount'              => $Amount,
                'PartyA'              => $GLOBALS['mc_mpesa_shortcode'],
                'PartyB'              => $PartyB,
                'Remarks'             => $Remarks,
                'QueueTimeOutURL'     => $GLOBALS['mc_mpesa_timeout_url'],
                'ResultURL'           => $GLOBALS['mc_mpesa_result_url'],
                'Occasion'            => $Occasion
            );

            $data_string = json_encode( $curl_post_data );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            return $response = curl_exec( $curl );
        }

        /**
         * B2B API
         * @param $Amount - Amount
         * @param $PartyB - Organization’s short code receiving the funds being transacted.
         * @param $commandID - Unique command for each transaction - BusinessPayBill | MerchantToMerchantTransfer | MerchantTransferFromMerchantToWorking |  MerchantServicesMMFAccountTransfer |  AgencyFloatAdvance
         * @param $AccountReference - Account Reference mandatory for “BusinessPaybill” CommandID.
         * @param $RecieverIdentifierType - Type of organization receiving the funds being transacted - MSISDN | Till Number | Shortcode
         * @param $Remarks - Comments that are sent along with the transaction.
         * @return mixed-string
         */
        public static function b2b( $args )
        {
            $Amount                 = $args['amount'];
            $PartyB                 = $args['receiver'];
            $RecieverIdentifierType = $args['reciever_type'];
            $CommandID              = isset( $args['command'] ) ? $args['command'] : "BusinessPayBill"; 
            $AccountReference       = isset( $args['reference'] ) ? $args['reference'] : "Business PayBill";
            $Remarks                = isset( $args['remarks'] ) ? $args['remarks'] : "Business PayBill";

            $token                  = self::authenticate();
          
            $endpoint               = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/b2b/v1/paymentrequest' : 'https://sandbox.safaricom.co.ke/mpesa/b2b/v1/paymentrequest';

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array( 
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                ) 
            );

            $curl_post_data = array( 
                'Initiator'               => $GLOBALS['mc_mpesa_username'],
                'SecurityCredential'      => $GLOBALS['mc_mpesa_credentials'],
                'CommandID'               => $commandID,
                'SenderIdentifierType'    => $GLOBALS['mc_mpesa_type'],
                'RecieverIdentifierType'  => $RecieverIdentifierType,
                'Amount'                  => $Amount,
                'PartyA'                  => $GLOBALS['mc_mpesa_shortcode'],
                'PartyB'                  => $PartyB,
                'AccountReference'        => $AccountReference,
                'Remarks'                 => $Remarks,
                'QueueTimeOutURL'         => $GLOBALS['mc_mpesa_timeout_url'],
                'ResultURL'               => $GLOBALS['mc_mpesa_result_url']
            );

            $data_string = json_encode( $curl_post_data );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            return $response = curl_exec( $curl );
        }

        /**
         * C2B API
         * This API enables Paybill and Buy Goods merchants to integrate to M-Pesa and receive real time payments notifications.
         * @param $Amount - The amount being transacted.
         * @param $Msisdn - MSISDN ( phone number ) sending the transaction, start with country code without the plus( + ) sign.
         * @param $BillRefNumber -  Bill Reference Number ( Optional )
         * @param $CommandID - Unique command for each transaction type.
         * @return mixed-string
         */
        public static function  c2b( $args )
        {
            $Amount         = $args['amount'];
            $phone          = $args['phone'];
            $BillRefNumber  = isset( $args['reference'] ) ? $args['reference'] : rand( 0, 1000000 ); 
            $CommandID      = isset( $args['command'] ) ? $args['command'] : "CustomerPayBillOnline";

            $token          = self::authenticate();

            $endpoint       = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/c2b/v1/simulate' : 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/simulate';

            // Remove the plus sign before the customer's phone number if present
            if ( substr( $phone, 0,1 ) == "+" ) {
                $Msisdn = str_replace( "+", "", $phone );
            }
            
            // Correct phone number format
            if ( substr( $phone, 0,1 ) == "0" ) {
                $Msisdn = preg_replace('/^0/', $GLOBALS['mc_mpesa_country'], $phone);
            }

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array( 
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                ) 
            );

            $curl_post_data = array( 
                'ShortCode'       => $GLOBALS['mc_mpesa_shortcode'],
                'CommandID'       => $CommandID,
                'Amount'          => $Amount,
                'Msisdn'          => $Msisdn,
                'BillRefNumber'   => $BillRefNumber
            );

            $data_string = json_encode( $curl_post_data );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            return $response = curl_exec( $curl );
        }

        /**
         * Use this to initiate a balance inquiry request
         * @param string $CommandID - A unique command passed to the M-Pesa system.
         * @param string $IdentifierType -Type of organization receiving the transaction
         * @param string $Remarks - Comments that are sent along with the transaction.
         * @return array
         */
        public static function balance( $args )
        {
            $CommandID = isset( $args['command'] ) ? $args['command'] : "AccountBalanceRequest";
            $Remarks = isset( $args['remarks'] ) ? $args['remarks'] : "Account Balance Request on ".date('D M d, Y');
          
            $token = self::authenticate();

            $endpoint = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/accountbalance/v1/query' : 'https://sandbox.safaricom.co.ke/mpesa/accountbalance/v1/query';

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array( 
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                ) 
            );

            $curl_post_data = array( 
                'CommandID'           => $CommandID,
                'Initiator'           => $GLOBALS['mc_mpesa_username'],
                'SecurityCredential'  => $GLOBALS['mc_mpesa_credentials'],
                'PartyA'              => $GLOBALS['mc_mpesa_shortcode'],
                'IdentifierType'      => $GLOBALS['mc_mpesa_type'],
                'Remarks'             => $Remarks,
                'QueueTimeOutURL'     => $GLOBALS['mc_mpesa_timeout_url'],
                'ResultURL'           => $GLOBALS['mc_mpesa_result_url']
            );

            $data_string = json_encode( $curl_post_data );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            return $response = curl_exec( $curl );
        }

        /**
         * Use this function to make a transaction status request
         * @param $TransactionID - Organization Receiving the funds.
         * @param $CommandID - Unique command for each transaction type, possible values are: TransactionStatusQuery.
         * @param $Remarks -    Comments that are sent along with the transaction
         * @param $Occasion -   Optional Parameter
         * @return mixed-string
         */
        public static function check_status( $args )
        {
            $TransactionID  = $args['id'];
            $CommandID      = isset( $args['command'] ) ? $args['command'] : "TransactionStatusQuery";
            $Remarks        = isset( $args['remarks'] ) ? $args['remarks'] : "Transaction Status Query";
            $Occasion       = isset( $args['occassion'] ) ? $args['occassion'] : "";

            $token = self::authenticate();

            $endpoint = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/transactionstatus/v1/query' : 'https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query';

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array( 
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                ) 
            );

            $curl_post_data = array( 
                'Initiator'           => $GLOBALS['mc_mpesa_username'],
                'SecurityCredential'  => $GLOBALS['mc_mpesa_credentials'],
                'CommandID'           => $CommandID,
                'TransactionID'       => $TransactionID,
                'PartyA'              => $GLOBALS['mc_mpesa_shortcode'],
                'IdentifierType'      => $GLOBALS['mc_mpesa_type'],
                'ResultURL'           => $GLOBALS['mc_mpesa_result_url'],
                'QueueTimeOutURL'     => $GLOBALS['mc_mpesa_timeout_url'],
                'Remarks'             => $Remarks,
                'Occasion'            => $Occasion
            );

            $data_string = json_encode( $curl_post_data );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            return $response = curl_exec( $curl );
        }

        /**
         * Use this function to initiate an online customer checkout
         * @param $Amount - The amount to be transacted.
         * @param $PartyB - The organization shortcode receiving the funds
         * @param $PhoneNumber - The MSISDN sending the funds.
         * @param $AccountReference - Used with M-Pesa PayBills.
         * @param $TransactionDesc - A description of the transaction.
         * @param $Remark - Remarks
         *
         * @return mixed-string
         */
        public static function online_checkout( $args )
        {
            $Amount = $args['amount'];
            
            $phone = $args['phone'];

            /**
            * Remove the plus sign before the customer's phone number if present
            */
            if ( substr( $phone, 0,1 ) == "+" ) {
                $PhoneNumber = str_replace( "+", "", $phone );
            }

            /**
             * Correct phone number format
             */
            if ( substr( $phone, 0,1 ) == "0" ) {
                $PhoneNumber = preg_replace('/^0/', $GLOBALS['mc_mpesa_country'], $phone);
            }

            $AccountReference = isset( $args['reference'] ) ? $args['reference'] : "";
            $TransactionDesc = isset( $args['description'] ) ? $args['description'] : "Online Checkout";
            $Remarks = isset( $args['remarks'] ) ? $args['remarks'] : "";

            $timestamp = date( "YmdHis" );
            $password = base64_encode( $GLOBALS['mc_mpesa_shortcode'].$GLOBALS['mc_mpesa_passkey'].$timestamp );

            $endpoint = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest' : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

            $token = self::authenticate();

            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array( 
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                ) 
            );

            $curl_post_data = array( 
                'BusinessShortCode'   => $GLOBALS['mc_mpesa_shortcode'],
                'Password'            => $password,
                'Timestamp'           => $timestamp,
                'TransactionType'     => 'CustomerPayBillOnline',
                'Amount'              => $Amount,
                'PartyA'              => $PhoneNumber,
                'PartyB'              => $GLOBALS['mc_mpesa_shortcode'],
                'PhoneNumber'         => $PhoneNumber,
                'CallBackURL'         => $GLOBALS['mc_mpesa_callback_url'],
                'AccountReference'    => $AccountReference,
                'TransactionDesc'     => $TransactionDesc,
                'Remark'              => $Remarks
            );

            $data_string = json_encode( $curl_post_data );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );

            return $response = curl_exec( $curl );
        }

        /**
         * Initiate an STKPush Status Query request.
         * @param array/int $args Argument(s) to pass to function - transaction ID  
         * @return string
         */
        public static function stkpush( $args )
        {
            $checkoutRequestID  = is_array( $args ) ? $args['id'] : $args;
            $token              = self::authenticate();
            $endpoint           = ( $GLOBALS['mc_mpesa_env'] == 'live' ) ? 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query' : 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
            $timestamp          = date( "YmdHis" );
            $password           = base64_encode( $GLOBALS['mc_mpesa_shortcode'].$GLOBALS['mc_mpesa_password'].$timestamp );
            $curl               = curl_init();
            $curl_post_data     = array( 
                'BusinessShortCode'   => $GLOBALS['mc_mpesa_shortcode'],
                'Password'            => $password,
                'Timestamp'           => $timestamp,
                'CheckoutRequestID'   => $checkoutRequestID
            );
            $data_string        = json_encode( $curl_post_data );

            curl_setopt( $curl, CURLOPT_URL, $endpoint );
            curl_setopt( 
                $curl, 
                CURLOPT_HTTPHEADER, 
                array( 
                    'Content-Type:application/json',
                    'Authorization:Bearer '.$token 
                ) 
            );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_POST, true );
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $data_string );
            curl_setopt( $curl, CURLOPT_HEADER, false );
            
            return $response = curl_exec( $curl );
        }

        /**
         * Accept transaction
         * @param int $id Third Party Transaction ID
         */
        public static function proceed( $id = 0 )
        {
            header("Access-Control-Allow-Origin: *");
            header('Content-Type:Application/json' );
            echo json_encode( [ 
              'ResponseCode'        => 0, 
              'ResponseDesc'        => 'Success',
              'ThirdPartyTransID'   => $id
            ] );
        }

        /**
         * Reject transaction
         * @param int $id Third Party Transaction ID
         */
        public static function reject( $id = 0 )
        {
            header("Access-Control-Allow-Origin: *");
            header('Content-Type:Application/json' );
            echo json_encode( [ 
              'ResponseCode'        => 1, 
              'ResponseDesc'        => 'Failed',
              'ThirdPartyTransID'   => $id
            ] );
        }
    }
}

/**
 * Wrapper function for @see Mpesa::c2b()
 * @param array $args Arguments to pass to function
 * @return string
 */
function  mc_mpesa_c2b( $args ) 
{ 
    if ( ! is_array( $args ) ) {
        $args['amount'] = func_get_arg(0);
        $args['phone'] = func_get_arg(1);
        $args['command'] = func_get_arg(2);
    }

    return MyMPesa::c2b( $args ); 
}

/**
 * Wrapper function for @see Mpesa::b2c()
 * @param array $args Arguments to pass to function
 * @return string
 */
function  mc_mpesa_b2c( $args ) 
{
    if ( ! is_array( $args ) ) {
        $args['amount'] = func_get_arg(0);
        $args['phone'] = func_get_arg(1);
        $args['command'] = func_get_arg(2);
    }
     
    return MyMPesa::b2c( $args ); 
}

/**
 * Wrapper function for @see Mpesa::b2b()
 * @param array $args Arguments to pass to function
 * @return string
 */
function  mc_mpesa_b2b( $args ) 
{ 
    if ( ! is_array( $args ) ) {
        $args['amount'] = func_get_arg(0);
        $args['phone'] = func_get_arg(1);
        $args['command'] = func_get_arg(2);
    }
    
    return MyMPesa::b2b( $args ); 
}

/**
 * Wrapper function for @see Mpesa::online_checkout()
 * @param array $args Arguments to pass to function
 * @return string
 */
function  mc_mpesa_checkout( $args )
{ 
    if ( ! is_array( $args ) ) {
        $args['amount']     = func_get_arg(0);
        $args['phone']      = func_get_arg(1);
        $args['command']    = func_get_arg(2);
    }
    
    return MyMPesa::online_checkout( $args ); 
}

/**
 * Wrapper function for @see Mpesa::check_status()
 * @param array $args Arguments to pass to function
 * @return string
 */
function  mc_mpesa_check_status( $args )
{
    if ( ! is_array( $args ) ) {
        $args['amount'] = func_get_arg(0);
        $args['phone'] = func_get_arg(1);
        $args['command'] = func_get_arg(2);
    }
    
    return MyMPesa::check_status( $args ); 
}

/**
 * Wrapper function for @see Mpesa::reverse()
 * @param array $args Arguments to pass to function
 * @return string
 */
function  mc_mpesa_reverse( $args )
{ 
    if ( ! is_array( $args ) ) {
        $args['amount'] = func_get_arg(0);
        $args['phone'] = func_get_arg(1);
        $args['command'] = func_get_arg(2);
    }
    
    return MyMPesa::reverse( $args ); 
}

/**
 * Wrapper function for @see Mpesa::balance()
 * @param array $args Arguments to pass to function
 * @return string
 */
function  mc_mpesa_balance( $args )
{ 
    if ( ! is_array( $args ) ) {
        $args['amount'] = func_get_arg(0);
        $args['phone'] = func_get_arg(1);
        $args['command'] = func_get_arg(2);
    }
    
    return MyMPesa::balance( $args ); 
}

/**
 * Wrapper function for @see MyMPesa::stkpush()
 * @param array $args Arguments to pass to function
 * @return string
 */
function  mc_mpesa_stk_push( $args )
{ 
    if ( ! is_array( $args ) ) {
        $args['amount'] = func_get_arg(0);
        $args['phone'] = func_get_arg(1);
        $args['command'] = func_get_arg(2);
    }
    
    return MyMPesa::stkpush( $args ); 
}

/**
 * Wrapper function for @see Mpesa::register()
 * @return string
 */
function  mc_mpesa_register_urls()
{
    return MyMPesa::register(); 
}

/**
 * Wrapper for @see MyMPesa::proceed()
 * @param $id Transaction id
 * @return string
 */
function mc_mpesa_proceed( $id )
{
    return MyMPesa::proceed( $id );
}

/**
 * Wrapper function for @see Mpesa::reject()
 * @param $id Transaction id
 * @return string
 */
function mc_mpesa_reject( $id )
{
    return MyMPesa::reject( $id );
}

/**
 * Function to process the response from Safaricom MyMPesa
 * @return object Response body
 */
function mc_mpesa_process_response()
{
    $response = json_decode( file_get_contents('php://input') );
    return $response -> Body;
}
