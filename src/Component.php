<?php
namespace cncDonation;

class Component {

	private $seed;
	private $store_name;
	private $api_key;
	private $language;
	private $db_table = 'cnc_donation';
	private $providers = ['cib', 'paypal'];

	function __construct()
	{
		global $wpdb;
		$this->db = $wpdb;
		$this->db_table = $this->db->prefix . $this->db_table;

		// Call in config
		$cac_donation_config = include(WP_PLUGIN_DIR . '/cnc-donation/config.php');
		$this->seed = $cac_donation_config['seed'];
		$this->store_name = $cac_donation_config['store_name'];
		$this->api_key = $cac_donation_config['api_key'];
		// Check for WPML language code
		if (defined('ICL_LANGUAGE_CODE')) {
			$this->language = ICL_LANGUAGE_CODE;
		} else {
			$this->language = substr(get_bloginfo ( 'language' ), 0, 2);
		}
	}

	/**
	 * Tasks for plugin activation
	 */
	public function pluginActivate()
	{
		
		$charset_collate = $this->db->get_charset_collate();
		if ($this->db->get_var("show tables like '{$this->db_table}'") != $this->db_table) {
			$sql = "CREATE TABLE " . $this->db_table . " (
				`id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
				`order_id` CHAR(32) NOT NULL,
				`transaction_id` CHAR(32) NOT NULL,
				`tdate` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
				`ldate` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
				`type` CHAR (30) NOT NULL,
				`status` CHAR (30) NOT NULL,
				`amount` INT(11) NOT NULL,
				`provider` CHAR (30),
				UNIQUE KEY id (id)
				) $charset_collate;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}
	}

	/**
	 * Tasks for plugin uninstall
	 */
	public function pluginUninstall()
	{
		$this->db->query("DROP TABLE IF EXISTS {$this->db_table}");
		wp_clear_scheduled_hook( 'recurring_payment' );
	}


	/**
	 * Set store credentials for PMGW request
	 * @param object $config PMGW config
	 */
	private function setStore($config)
	{
		$config->storeName = $this->store_name;
		$config->apiKey = $this->api_key;
		$config->testMode = true;
		return $config;
	}

	/**
	 * Set single payment for PMGW request
	 * @param  int $amount  donation amount
	 * @return object          PMGW request
	 */
	private function startSP($amount, $provider = 'CIB')
	{
		try {
			/**
			 * Initialize new PMGW request
			 * @var object
			 */
			$request = new \BigFish\PaymentGateway\Request\Init();

			$request->setProviderName($provider) // A felhasználó által választott fizetési mód
				->setResponseUrl($this->responseURL) // Visszatérési URL
				->setAmount($amount) // Összeg
				->setCurrency("HUF") // Valutanem
				->setOrderId($this->orderID) // Megrendelés azonosító
				->setLanguage($this->language); // Nyelv

			/**
			 * Init PMGW transaction
			 * @var object
			 */
			$response = \BigFish\PaymentGateway::init($request);

			if ($response->ResultCode == "SUCCESSFUL" && $response->TransactionId) {
				/**
				 * Start PMGW transaction
				 */
				$this->storeTransaction($response->TransactionId, 'single', $amount, $provider);
				$start_response = \BigFish\PaymentGateway::start(new \BigFish\PaymentGateway\Request\Start($response->TransactionId));
				return $start_response;
			}
			return $response;


		} catch (\BigFish\PaymentGateway\Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Set recurring payment for PMGW request
	 * @param  int $amount  donation amount
	 * @return object          PMGW request
	 */
	private function startRP($amount, $provider = 'PayPal')
	{
		try {
			/**
			 * Initialize new PMGW request
			 * @var object
			 */
			$request = new \BigFish\PaymentGateway\Request\Init();

			$request->setProviderName($provider) // A felhasználó által választott fizetési mód
				->setResponseUrl($this->responseURL) // Visszatérési URL
				->setOneClickPayment(true) // One click payment for recurring payment
				->setAmount($amount) // Összeg
				->setCurrency("HUF") // Valutanem
				->setOrderId($this->orderID) // Megrendelés azonosító
				->setLanguage($this->language); // Nyelv

			/**
			 * Init PMGW transaction
			 * @var object
			 */
			$response = \BigFish\PaymentGateway::init($request);

			if ($response->ResultCode == "SUCCESSFUL" && $response->TransactionId) {
				/**
				 * Start PMGW transaction
				 */
				$this->storeTransaction($response->TransactionId, 'recurring', $amount, $provider);
				$start_response = \BigFish\PaymentGateway::start(new \BigFish\PaymentGateway\Request\Start($response->TransactionId));
				return $start_response;
			}
			return $response;


		} catch (\BigFish\PaymentGateway\Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Init and start recurring payment
	 * 
	 * @param int $amount Amount of donation
	 * @param strint $transaction_id Transaction Id
	 * @return object|string
	 * @access public
	 */
	private function repeatRP($amount, $transaction_id)
	{
		try {
			$initRPRequest = new \BigFish\PaymentGateway\Request\InitRP();
			$initRPRequest->setReferenceTransactionId($transaction_id)
				->setResponseUrl($this->responseURL)
				->setAmount($amount)
				->setCurrency('HUF')
				->setOrderId($this->orderID);
			$initRPResponse = \BigFish\PaymentGateway::initRP($initRPRequest);
			
			if ($initRPResponse->ResultCode == "SUCCESSFUL" && $initRPResponse->TransactionId) {
				$startRPResponse = \BigFish\PaymentGateway::startRP(new \BigFish\PaymentGateway\Request\StartRP($initRPResponse->TransactionId));
				return $startRPResponse;
			}
			return $initRPResponse;
		} catch (\BigFish\PaymentGateway\Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Store PMGW transaction details
	 * @param string $transaction_id Referenced transaction ID
	 * @return int,boolean  Affected number of rows or FALSE
	 */
	private function storeTransaction($transaction_id, $type, $amount, $provider)
	{
		return $this->db->query( 
			$this->db->prepare("INSERT INTO {$this->db_table} (`id`, `order_id`, `transaction_id`, `tdate`, `type`, `status`, `amount`, `provider`) VALUES ( NULL, %s, %s, %s, %s, %s, %d, %s )",
			$this->orderID, $transaction_id, current_time('mysql', 1), $type, 'pending', $amount, $provider) 
		);
	}

	/**
	 * Update transaction status
	 * @param  string $transaction_id Reference transaction ID
	 * @param  string $status         Status value
	 * @return int,boolean  Affected number of rows or FALSE
	 */
	private function updateTransactionStatus($transaction_id, $status)
	{
		return $this->db->update($this->db_table, 
			['status' => $status, 'ldate' => current_time('mysql', 1)],
			['transaction_id' => $transaction_id],
			['%s', '%s'],
			['%s']
		);
	}

	/**
	 * Print donation form markup
	 */
	private function renderDonationForm()
	{
		echo '<div class="cnc-donation">';
		// echo '<form class="donation-form" action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
		echo '<form class="donation-form" action="' . get_bloginfo( 'url' ) . '/cnc-donation" method="post">';
		echo '<label for="donation-amount">' . __('Amount of donation', 'cnc-donation') . ':</label>';
		echo '<div class="radio-wrap"><input type="radio" name="donation-amount" value="5000" class="donation-amount" checked="checked" /> 5.000 Ft.</div>';
		echo '<div class="radio-wrap"><input type="radio" name="donation-amount" value="10000" class="donation-amount" /> 10.000 Ft.</div>';
		echo '<div class="radio-wrap"><input type="radio" name="donation-amount" value="20000" class="donation-amount" /> 20.000 Ft.</div>';
		echo '<div class="radio-wrap"><input type="radio" name="donation-amount" value="custom" class="donation-amount" /> ' . __('Given amount', 'cnc-donation') . '</div>';
		echo '<div class="given-amount-wrapper"><label for="given-amount">' . __('Given amount of donation', 'cnc-donation') . ':</label>';
		echo '<input type="text" name="given-amount" /> Ft.</div>';
		echo '<label for="donation-method">' . __('Donation regularity', 'cnc-donation') . ':</label>';
		echo '<div class="radio-wrap"><input type="radio" name="donation-method" value="1" />' . __('Monthly, regular', 'cnc-donation') . '</div>';
		echo '<div class="radio-wrap"><input type="radio" name="donation-method" value="0" checked="checked" />' . __('Single', 'cnc-donation') . '</div>';
		echo '<label for="provider">' . __('Provider', 'cnc-donation') . ':</label>';
		echo '<div class="radio-wrap"><input type="radio" name="provider" value="CIB" /><span class="provider-icon">' . __('CIB', 'cnc-donation') . '</span></div>';
		echo '<div class="radio-wrap"><input type="radio" name="provider" value="PayPal" checked="checked" /><span class="provider-icon">' . __('PayPal', 'cnc-donation') . '</span></div>';
		echo '<input class="form-submit" type="submit" name="donation-submitted" value="' . __('Send', 'cnc-donation') . '" />';
		echo '</div>';
	}

	private function generateTransactionValues()
	{
		$this->orderID = md5(time().$this->seed);
		$this->responseURL = $_SERVER['HTTP_REFERER'];
	}

	/**
	 * Analayze donation form data and control behaviour
	 * @return bool Form processing successful
	 */
	public function processDonationForm()
	{
		$this->initConfig();
		// Check custom given donation amount
		if ($_POST['donation-amount']!='custom' && empty($_POST['given-amount'])) {
			$amount = intval($_POST['donation-amount']);
		} else {
			$amount = intval($_POST['given-amount']);
		}
		// Amount value validated
		if ($amount && $provider = $this->sanitizeProvider($_POST['provider'])) {
			$this->generateTransactionValues();
			if (intval($_POST['donation-method']) == 1) {
				// Recurring payment selected
				$rp_response = $this->startRP($amount);
			} else {
				// Single payment selected
				$sp_response = $this->startSP($amount, $provider);
			}
		}
	}

	/**
	 * Handles form shortcode generation
	 */
	public function donationFormShortcode()
	{
		if (!isset($_POST['donation-submitted'])) {
			if (isset($_GET['TransactionId']) && !empty($_GET['TransactionId'])) {
				$transaction_id = sanitize_text_field($_GET['TransactionId']);
				if($this->checkPaymentResult($transaction_id)) {
					// Successful transaction
					$this->updateTransactionStatus($transaction_id, 'successful');
				} else {
					// Transaction failed
					$this->updateTransactionStatus($transaction_id, 'failed');
				}
			} else {
				$this->renderDonationForm();
			}
		} 
	}

	/**
	 * Inititlaize PMGW connnection
	 */
	private function initConfig()
	{
		// Get PMGW Config instance
		$config = new \BigFish\PaymentGateway\Config();
		// Set store credentials
		$config = $this->setStore($config);
		// Set PMGW configuration
		\BigFish\PaymentGateway::setConfig($config);
	}

	/**
	 * Check PMGW transaction was successful
	 * @param  string $transaction_id Transaction reference ID
	 * @return boolean                 TRUE if successful :)
	 */
	private function checkPaymentResult($transaction_id)
	{
		$resultResponse = \BigFish\PaymentGateway::result(new \BigFish\PaymentGateway\Request\Result($transaction_id));
		if ($resultResponse->ResultCode == 'SUCCESSFUL') {
			return true;
		} else {
			return false;
		}
	}

	public function recurringPaymentCron()
	{
		$transactions = $this->getRecurringList();
		foreach ($transactions as $transaction) {
			$result = $this->repeatRP($transaction->transaction_id, $transaction->amount);
			if ($this->checkPaymentResult($transaction->transaction_id)) {
				$this->updateTransactionStatus($transaction->transaction_id, 'successful');
			} else {
				$this->updateTransactionStatus($transaction->transaction_id, 'failed');
			}
		}
	}

	/**
	 * Function for cron_schedules filter to add new schedule
	 * @param  [type] $schedules [description]
	 * @return [type]            [description]
	 */
	public function cronDefiner($schedules){
		$schedules['monthly'] = array(
			'interval'=> 2592000,
			'display'=>  __('Once Every 30 Days')
			);
		return $schedules;
	}

	/**
	 * Get successful recurring payment reference IDs
	 * @return array,boolean Reault list or FALSE
	 */
	private function getRecurringList()
	{
		$query = "SELECT `transaction_id`, `amount` 
			FROM {$this->db_table} 
			WHERE `type` LIKE 'recurring' AND `status` LIKE 'successful'";
		return $this->db->get_results($query);
	}

	public function setBodyClass($classes)
	{
		$classes[] = 'cnc-donation-page';
		return $classes;
	}

	/**
	 * Validate provider name
	 * @param  string $provider Provider name
	 * @return string,boolean           Sanitized provider name or FALSE
	 */
	private function sanitizeProvider($provider)
	{
		$provider = sanitize_text_field($provider);
		if (in_array(strtolower($provider), $this->providers)) {
			return $provider;
		} else {
			return false;
		}
	}
}
