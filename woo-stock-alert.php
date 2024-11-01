<?php
/*
	Plugin Name: Woo Stock Alert Lite
	Plugin URI: http://www.wpfruits.com
	Description: Woo Stock Alert Plugin is an effective woo commerce plugin which sends mail notification to customers apprising them of availability of the products which were out of stock during their visit. This plugin is highly useful for multiple woocommerce platforms and online shops which need a must have medium to update their customers about the availability of certain products in stock. Woo Stock plugin swiftly integrates with every site and automatically send emails to customers updating them about stock availability. You can pre-set your own mail form, subject, mail content and customised thanks message into this plugin.
	Version: 1.0.0
	Author: WPFruits
*/

/********************************************************
 TEXT DOMAIN
*********************************************************/

function stock_alert_load_textdomain() {
  load_plugin_textdomain( 'stock-alert', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'stock_alert_load_textdomain' );

/********************************************************
 AJAX URL
*********************************************************/

function stock_alert_ajaxurl() {
?>
	<script type="text/javascript">
		var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
	</script>
<?php
}
add_action('wp_head','stock_alert_ajaxurl');

/********************************************************
 PLUGIN CLASS
*********************************************************/

class stockalertficationLite
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	protected $options;

	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'woocommerce_product_meta_start', 'stockalertadd_custom_field', 0 );
		add_action('wp_footer', 'stockalert_front_enqueue_scripts', 0 );
		add_action('admin_enqueue_scripts','stockalert_admin_enqueue_scripts');
		add_action( 'admin_menu', array( $this, 'stockalert_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'stockalert_page_init' ) );
	}

	/**
	 * Add plugin page
	 */
	public function stockalert_plugin_page()
	{
		add_submenu_page(
	        'woocommerce',
	        __('Stock Alert', 'stock-alert'),
	        __('Stock Alert', 'stock-alert'),
	        'manage_options',
	        'stockalert-setting-submenu',
	        array( $this, 'stockalert_admin_page' )
	    );
	}

	/**
	 * Plugin page callback
	 */
	public function stockalert_admin_page()
	{
		// Set class property
		$this->options = get_option( 'stockalert_options' );
?>
		<div class="wrap">
			<h2><?php _e('Stock Alerts Settings', 'stock-alert'); ?></h2>
			<form method="post" action="options.php">
			<hr>
			<?php
				settings_fields( 'stockalert_option_group' );   
				do_settings_sections( 'stockalert-setting-admin' );
				?>
			<hr/>
				<?php
				submit_button();
			?>
			</form>
		</div>
<?php
	}

	/**
	 * Register and add settings
	 */
	public function stockalert_page_init()
	{
		register_setting(
			'stockalert_option_group', // Option group
			'stockalert_options', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'wsa_setting_section', // ID
			'', // Title
			array( $this, 'stockalert_print_section_info' ), // Callback
			'stockalert-setting-admin' // Page
		);

		add_settings_field(
			'on-off', // ID
			__('Show Email Form','stock-alert'), // Title
			array( $this, 'stockalert_onoff_callback' ), // Callback 
			'stockalert-setting-admin', // Page
			'wsa_setting_section' // Section
		);

		add_settings_field(
			'mail-subject', // ID
			__('Mail Subject','stock-alert'), // Title
			array( $this, 'stockalert_mailsubject_callback' ), // Callback 
			'stockalert-setting-admin', // Page
			'wsa_setting_section' // Section
		);

		add_settings_field(
			'mail-content', // ID
			__('Mail Content','stock-alert'), // Title
			array( $this, 'stockalert_mailcontent_callback' ), // Callback 
			'stockalert-setting-admin', // Page
			'wsa_setting_section' // Section
		);

		add_settings_field(
			'thanks-msg', // ID
			__('Thank You Message','stock-alert'), // Title
			array( $this, 'stockalert_thankyou_callback' ), // Callback 
			'stockalert-setting-admin', // Page
			'wsa_setting_section' // Section
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input )
	{
		$new_input = array();

		if( isset( $input['wsa-onoff-checkbox'] ) )
			$new_input['wsa-onoff-checkbox'] = sanitize_text_field( $input['wsa-onoff-checkbox'] );

		if( isset( $input['wsa-mail-subject'] ) )
			$new_input['wsa-mail-subject'] = sanitize_text_field( $input['wsa-mail-subject'] );

		if( isset( $input['wsa-mail-content'] ) )
            $new_input['wsa-mail-content'] = force_balance_tags($input['wsa-mail-content']);

        if( isset( $input['wsa-thankyou'] ) )
            $new_input['wsa-thankyou'] = force_balance_tags($input['wsa-thankyou']);

		return $new_input;
	}

	/** 
	 * Print the Section text
	 */
	public function stockalert_print_section_info()
	{
	
	}

	/** 
	 * NOTIFICATION ON OFF
	 */
	public function stockalert_onoff_callback()
	{	
		if($this->options == ''){
			$checkedonoff = 'checked';	
		}else{
			$checkedonoff = (isset($this->options["wsa-onoff-checkbox"])) ? 'checked' : '';
		}
		echo '<span><p class="wsa-onoff-checkbox"><input type="checkbox" name="stockalert_options[wsa-onoff-checkbox]" id="wsa-onoff-checkbox" value="0"'. $checkedonoff .'/><label for="wsa-onoff-checkbox"></label></p></span>';
	}

	/** 
     * MAIL SUBJECT
     */
    public function stockalert_mailsubject_callback()
    {
        printf(
            '<input type="text" id="wsa-mail-subject" class="wsa-mail-subject" name="stockalert_options[wsa-mail-subject]" value="%s" />',
            isset( $this->options['wsa-mail-subject'] ) ? esc_attr( $this->options['wsa-mail-subject']) : 'Stock Alert'
        );
    }

    /** 
     * MAIL CONTENT
     */
    public function stockalert_mailcontent_callback()
    {
        printf(
        	'<textarea id="wsa-mail-content" name="stockalert_options[wsa-mail-content]" rows="10" cols="55" value="">%s</textarea>',
        	isset( $this->options["wsa-mail-content"] ) ? esc_attr( $this->options["wsa-mail-content"] ) : '<h3>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries</h3>'
        );
    }

    /** 
     * THANK YOU
     */
    public function stockalert_thankyou_callback()
    {
        printf(
            '<input type="text" id="wsa-thankyou" class="wsa-thankyou" name="stockalert_options[wsa-thankyou]" value="%s" />',
            isset( $this->options['wsa-thankyou'] ) ? esc_attr( $this->options['wsa-thankyou']) : 'Thanks For Subscribe'
        );
    }

}

$stockalertficationLite = new stockalertficationLite();

/********************************************************
 CALL AJAX FUNCTION
*********************************************************/

include_once(plugin_dir_path( __FILE__ ) . "ajax-fn/wsa-ajax-functions.php");

/********************************************************
 ENQUEUE SCRIPTS
*********************************************************/

function stockalertadd_custom_field() { 

	$options = get_option( 'stockalert_options' );
	global $product;
	if(isset($options['wsa-onoff-checkbox']) || $options == ''){
			$meta = '_stock_alert_lite';
			$productid = $product->get_id(); 
			$allvalues = get_post_meta( $productid, $meta );
		    $old_values = json_encode($allvalues);
		    if( $product->is_type( 'simple' ) ){
				$stock = $product->is_in_stock();
				if($stock != '1'){
			  	?>
		 		<div class='sa-email' style="position:relative; width:100%; display:inline-block;">
						<span class='sa-email-input'>
						<span class='sa-label'><?php _e("Subscribe for Stock Alert","stock-alert") ?></span>
						<span class='sa-new-input-wrap'>
							<input type="hidden" id="sa-new-product-id" name="sa-product-id" value="<?php echo $productid; ?>" />
							<input type="text" id="sa-new-email" name="sa-email" placeholder="E-mail"/>
						</span>
						<span class='sa-old-all-data'>
							<?php 
							if(!isset($allvalues)){
								if($allvalues[0] != 'null'){
									foreach ($allvalues as $newvalue) {
										$jsonvalue = json_decode($newvalue);
										foreach ($jsonvalue as $value) {
											echo '<span class="sa-old-input-wrap"><input type="hidden" id="sa-email" value="'.$value->email.'" />';
										}
									}
								} 
							}
							?>
						</span>
							<input type="hidden" id="sa-all-data" name="sa-all-data" value='<?php echo stripcslashes($old_values); ?>' />
						<span id="sa-submit-wrap"><button id="sa-submit"><?php _e("Subscribe", "stock-alert") ?></button></span>
						</span>
				</div>

				<?php
				}
			} elseif( $product->is_type( 'variable' ) ){
				$stk = '';
				$available_variations = $product->get_available_variations();
				foreach ($available_variations as $key) {
					$stock = $key['availability_html'];
					if($stock == 'Out of stock'){
						$stk = 0;
					}
				}
				if($stk == 0){
				?>
				<div class='sa-email' style="position:relative; width:100%; display:inline-block;">
						<span class='sa-email-input'>
						<span class='sa-label'><?php _e("Subscribe for Stock Alert","stock-alert") ?></span>
						<span class='sa-new-input-wrap'>
							<input type="hidden" id="sa-new-product-id" name="sa-product-id" value="<?php echo $productid; ?>" />
							<input type="text" id="sa-new-email" name="sa-email" placeholder="E-mail"/>
						</span>
						<span class='sa-old-all-data'>
							<?php 
							if(!isset($allvalues)){
								if($allvalues[0] != 'null'){
									foreach ($allvalues as $newvalue) {
										$jsonvalue = json_decode($newvalue);
										foreach ($jsonvalue as $value) {
											echo '<span class="sa-old-input-wrap"><input type="hidden" id="sa-email" value="'.$value->email.'" />';
										}
									}
								} 
							}
							?>
						</span>
							<input type="hidden" id="sa-all-data" name="sa-all-data" value='<?php echo stripcslashes($old_values); ?>' />
						<span id="sa-submit-wrap"><button id="sa-submit"><?php _e("Subscribe", "stock-alert") ?></button></span>
						</span>
				</div>
			<?php
				}
			}

	}
}

/********************************************************
 UPDATE PRODUCT
*********************************************************/

function stock_alert_save_post()
{	
	$product = new WC_Product(get_the_ID()); 
	$wsa_options = get_option( 'stockalert_options' );

	$productid = get_the_ID(); 
	$product_image = $product->get_image();
	$product_title = '<a href="'.get_permalink($productid).'">'.get_the_title($productid).'</a>';
	$product_price = $product->price;
	$currency_symbol = get_woocommerce_currency_symbol();
	$product_url = get_permalink();

	$wsa_content = (isset($wsa_options['wsa-mail-content'])) ? $wsa_options['wsa-mail-content'] : '<h3>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries</h3>';
	$footer_link = '<a href="wpfruits.com">WPFruits</a>';
	$message = '<!DOCTYPE HTML>'. 
				'<html xmlns="http://www.w3.org/1999/xhtml">'.
				'<head>'. 
				'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'. 
				'<title>Stock Alert</title>'. 
				'</head>'. 
				'<body>'. 
				'<div id="outer" style="width: 100%;margin-top: 10px;">'.  
					'<div id="inner" style="width: 100%; background-color: #fff;font-family: Open Sans,Arial,sans-serif;">'.
						'<div id="inner" style=" padding:10px;width: 100%;background-color: #fff;font-family: Open Sans,Arial,sans-serif;font-size: 13px;font-weight: normal;line-height: 1.4em;color: #444;margin-top: 10px;">'. 
							$wsa_content.
						'</div>'.
						'<div style="width: 30%;padding: 10px;float: left;display: block;">'.
							$product_image.
						'</div>'.
						'<div style="width: 40%; padding: 20px 10px 20px 10px;display: block;float: left;">'.
							'<h2>'.$product_title.'</h2>'.
							'<h3><span>'.$currency_symbol.'</span><span>'.$product_price.'</span</h3>'.
							'<br>'.
							'<h3><a href="'.$product_url.'">'.__("Get Product", "stock-alert").'</a></h3>'.
						'</div><div style="clear:both;"></div>'.
					'</div>'.   
				'</div>'. 
				'<div id="footer" style="width: 100%;height: 40px;margin: 0 auto;text-align: center;padding: 10px;font-family: Verdena;background-color: #E2E2E2;">'. 
				   'All rights reserved '.$footer_link.
				'</div>'. 
				'</body>'.
				'</html>';
				 //Prepare headers for HTML    
				// $headers  = 'MIME-Version: 1.0' . "\r\n";    
				// $headers .= 'Content-type: text/html; charset=iso-8859-1' . '\r\n';     
	/*EMAIL TEMPLATE ENDS*/ 
	$subject = (isset($wsa_options['wsa-mail-subject'])) ? $wsa_options['wsa-mail-subject'] : 'Stock Alert';  //change subject of email 
	$meta = '_stock_alert_lite';
	
	$stock = $product->is_in_stock();
	$allvalues = get_post_meta( $productid, $meta );
	if($allvalues != ''){
		$unset_queue = array();
		foreach ( $allvalues as $i => $item ){
			$newitem = json_decode($item);
			foreach ($newitem as $key => $value) {
			    if($stock == '1'){
			    	wp_mail( $value->email, $subject, $message, 'Content-type: text/html; charset=iso-8859-1' . '\r\n' );
			        $unset_queue[] = $key;
			    }
			}
		}
		foreach ( $unset_queue as $index ){	
		    unset($newitem[$index]);
		}
		// rebase the array
		$newitem = array_values($newitem);
		$new_json_data = json_encode($newitem);
		if($new_json_data == 'null'){
			update_post_meta( $productid, $meta, '[]' );
		}else{
			update_post_meta( $productid, $meta, $new_json_data );
		}
	}
}
add_action( 'save_post', 'stock_alert_save_post' );

function stockalert_admin_enqueue_scripts(){
	if(isset($_REQUEST['page']) && $_REQUEST['page']=="stockalert-setting-submenu") {
		wp_enqueue_script('stockalert-front-script', plugins_url('js/wsa-backend-custom.js',__FILE__),'','', true);
		wp_enqueue_style('wsa-admin-style', plugins_url('css/wsa-backend.css',__FILE__));
	}
}

function stockalert_front_enqueue_scripts(){
	wp_enqueue_script('stockalert-front-script', plugins_url('js/wsa-front-custom.js',__FILE__),'','', true);
	include_once(plugin_dir_path( __FILE__ ) . "css/wsa-front-customcss.php");
}