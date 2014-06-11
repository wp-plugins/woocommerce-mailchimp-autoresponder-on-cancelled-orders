<?php

/**
 *  Class: WC_Mailchimp_Autoresponder_Cancelled_Orders
 */
class WC_Mailchimp_Autoresponder_Cancelled_Orders extends WC_Integration {

    /**
     * Init and hook in the integration.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        $this->id         = 'wc_mailchimp_autoresponder_cancelled_orders';
        $this->method_title       = __( 'Mailchimp Autoresponder on Cancelled Orders', 'wmaco' );
        $this->method_description = __( 'Send an email to user who doesn\'t end the order proccess', 'wmaco' );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->mailchimp_api_key = $this->get_option( 'mailchimp_api_key' );
        $this->mailchimp_list_id = $this->get_option( 'mailchimp_list_id' );

        // Actions
        add_action( 'woocommerce_update_options_integration_wc_mailchimp_autoresponder_cancelled_orders', array( $this, 'process_admin_options') );

        // Add user to list
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'add_user_to_list') );
        add_action( 'woocommerce_order_status_failed',    array( $this, 'add_user_to_list') );
    }

    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() 
    {
        $this->form_fields = array(
            'mailchimp_api_key' => array(
                'title'       => __( 'Mailchimp API Key', 'wmaco' ),
                'description'     => __( 'You can find these in Account Settings » Extras » API keys', 'wmaco' ),
                'type'        => 'text',
                'default'       => get_option( 'mailchimp_api_key' )
            ),
            'mailchimp_list_id' => array(
                'title'       => __( 'List ID', 'wmaco' ),
                'description'     => __( 'You can find these inside the list in Settings » List name & defaults', 'wmaco' ),
                'type'        => 'text',
                'default'       => get_option( 'mailchimp_list_id' )
            )
        );
    } // End init_form_fields()

    /**
     * Send the user to our MailChimp List
     * @param int $order_id 
     */
    function add_user_to_list( $order_id )
    {
        // Get the order email
        $object       = new WC_Order( $order_id );
        $recipient    = $object->billing_email;

        // Init mc Class
        $mc = new MailChimp($this->mailchimp_api_key);
        
        $result = $mc->call('lists/subscribe', array(
            'id'         => $this->mailchimp_list_id,
            'email'      => array( 'email' => $object->billing_email ),
            'merge_vars' => array(
                                'FNAME' => $object->billing_first_name,
                                'LNAME' => $object->billing_last_name
                            ),
            'double_optin'      => false,
            'update_existing'   => false,
            'replace_interests' => false,
            'send_welcome'      => false,
        ));
    } // End add_user_to_list
}


/**
 * Super-simple, minimum abstraction MailChimp API v2 wrapper
 * 
 * Uses curl if available, falls back to file_get_contents and HTTP stream.
 * This probably has more comments than code.
 *
 * Contributors:
 * Michael Minor <me@pixelbacon.com>
 * Lorna Jane Mitchell, github.com/lornajane
 * 
 * @author Drew McLellan <drew.mclellan@gmail.com> 
 * @version 1.1.1
 */
class MailChimp
{
    private $api_key;
    private $api_endpoint = 'https://<dc>.api.mailchimp.com/2.0';
    private $verify_ssl   = false;

    /**
     * Create a new instance
     * @param string $api_key Your MailChimp API key
     */
    function __construct($api_key)
    {
        $this->api_key = $api_key;
        list(, $datacentre) = explode('-', $this->api_key);
        $this->api_endpoint = str_replace('<dc>', $datacentre, $this->api_endpoint);
    }

    /**
     * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
     * @param  string $method The API method to call, e.g. 'lists/list'
     * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
     * @return array          Associative array of json decoded API response.
     */
    public function call($method, $args=array())
    {
        return $this->makeRequest($method, $args);
    }

    /**
     * Performs the underlying HTTP request. Not very exciting
     * @param  string $method The API method to be called
     * @param  array  $args   Assoc array of parameters to be passed
     * @return array          Assoc array of decoded result
     */
    private function makeRequest($method, $args=array())
    {      
        $args['apikey'] = $this->api_key;

        $url = $this->api_endpoint.'/'.$method.'.json';

        if (function_exists('curl_init') && function_exists('curl_setopt')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');       
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $json_data = json_encode($args);
            $result    = file_get_contents($url, null, stream_context_create(array(
                'http' => array(
                    'protocol_version' => 1.1,
                    'user_agent'       => 'PHP-MCAPI/2.0',
                    'method'           => 'POST',
                    'header'           => "Content-type: application/json\r\n".
                                          "Connection: close\r\n" .
                                          "Content-length: " . strlen($json_data) . "\r\n",
                    'content'          => $json_data,
                ),
            )));
        }

        return $result ? json_decode($result, true) : false;
    }
}
