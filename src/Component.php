<?php
namespace cncDonation;

class Component {

	private $seed;
	private $store_name;
	private $api_key;
	private $language;
	private $test;
	private $db_table = 'cnc_donation';
	private $providers = ['cib', 'paypal'];
	private $contact;

	function __construct()
	{
		global $wpdb;
		$this->plugin_path = plugin_dir_path(dirname(__FILE__));
		$this->plugin_url = plugin_dir_url(dirname(__FILE__));
		$this->db = $wpdb;
		$this->db_table = $this->db->prefix . $this->db_table;

		// Call in config
		$cac_donation_config = include(WP_PLUGIN_DIR . '/cnc-donation/config.php');
		$this->seed = $cac_donation_config['seed'];
		$this->store_name = $cac_donation_config['store_name'];
		$this->api_key = $cac_donation_config['api_key'];
		$this->test = $cac_donation_config['test'];
		// Check for WPML language code
		if (defined('ICL_LANGUAGE_CODE')) {
			$this->language = ICL_LANGUAGE_CODE;
		} else {
			$this->language = substr(get_bloginfo ( 'language' ), 0, 2);
		}

		add_action('wp_enqueue_scripts', [$this, 'registerScripts']);
	}

	/**
	 * Callback for register necessary scripts
	 */
	public function registerScripts()
	{
		wp_enqueue_style('cnc-donation-main', $this->plugin_url . '/assets/css/main.css');
		wp_register_script('cnc-donation-main', $this->plugin_url . '/assets/js/main.js', array('jquery'), '1', true);
		// Prepare script for use AJAX
		wp_localize_script( 'cnc-donation-main', 'cnc_donation_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		    'nonce'    => wp_create_nonce('cncdntn_nonce'),
	    ) );
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
				`client` TEXT,
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
		$config->testMode = $this->test;
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
				$this->storeTransaction($response->TransactionId, 'single', $amount, $provider, $this->contact);
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

			// Set extra Reference for recurring
			$extra = [
				'REFERENCE' => [
					'BILLINGFREQUENCY' => 1,
					'BILLINGPERIOD' => 'Month',
					'INITAMT' => $amount,
					// 30 days after in this format: 2016-08-26T08:29:56Z
					'PROFILESTARTDATE' => gmdate('Y-m-d\TH:i:s\Z', time() + (30 * 24 * 60 * 60)),
					'DESC' => 'Recurring donation for Transparency International Hungary',
				],
			];

			$request->setProviderName($provider) // Payment method
				->setResponseUrl($this->responseURL) // Response URL
				->setOneClickPayment(true) // One click payment for recurring payment
				->setAmount($amount) // Amount
				->setCurrency("HUF") // Currenvy
				->setOrderId($this->orderID) // Order ID
				->setLanguage($this->language) // Language
				->setExtra($extra);

			/**
			 * Init PMGW transaction
			 * @var object
			 */
			$response = \BigFish\PaymentGateway::init($request);

			if ($response->ResultCode == "SUCCESSFUL" && $response->TransactionId) {
				/**
				 * Start PMGW transaction
				 */
				$this->storeTransaction($response->TransactionId, 'recurring', $amount, $provider, $this->contact);
				$start_response = \BigFish\PaymentGateway::start(new \BigFish\PaymentGateway\Request\Start($response->TransactionId));
				return $start_response;
			}
			return $response;


		} catch (\BigFish\PaymentGateway\Exception $e) {
			return $e->getMessage();
		}
	}

	/**
	 * NO LONGER REQUIRED!
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
	private function storeTransaction($transaction_id, $type, $amount, $provider, $contact = [])
	{
		return $this->db->query(
			$this->db->prepare("INSERT INTO {$this->db_table} (`id`, `order_id`, `transaction_id`, `tdate`, `type`, `status`, `amount`, `provider`, `contact`) VALUES ( NULL, %s, %s, %s, %s, %s, %d, %s, %s )",
			$this->orderID, $transaction_id, current_time('mysql', 1), $type, 'pending', $amount, $provider, json_encode($contact))
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
		$view = new View();
		return $view->render('form-donation');
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
	 * Analayze donation form data and control behaviour
	 * @return bool Form processing successful
	 */
	public function processPopupDonationForm()
	{
		$this->initConfig();
		if ($package = intval($_POST['cnc-package-id'])) {
			$this->generateTransactionValues();
			$this->contact = [
				'email' => sanitize_text_field($_POST['supporter-email']),
				'name' => sanitize_text_field($_POST['supporter-name']),
			];
			switch ($package) {
				case 1:
					$amount = 1000;
					$rp_response = $this->startRP($amount);
					break;
				case 2:
					$amount = 5000;
					$rp_response = $this->startRP($amount);
					break;
				case 3:
					$amount = 10000;
					$rp_response = $this->startRP($amount);
					break;
				case 4:
					if (!empty($_POST['cnc-recurring-amount'])) {
						$amount = intval($_POST['cnc-recurring-amount']);
						$rp_response = $this->startRP($amount);
					} else {
						$amount = intval($_POST['cnc-single-amount']);
						$sp_response = $this->startSP($amount, 'CIB');
					}
					break;
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
				return $this->renderDonationForm();
			}
		} 
	}

	/**
	 * Handles results shortcode generation
	 */
	public function donationResultsShortcode()
	{
		if (!isset($_POST['donation-submitted'])) {
			if (isset($_GET['TransactionId']) && !empty($_GET['TransactionId'])) {
				$view = new View();
				$transaction_id = sanitize_text_field($_GET['TransactionId']);
				if($this->checkPaymentResult($transaction_id)) {
					// Successful transaction
					$type = $this->getTransactionType($transaction_id);
					switch ($type) {
						case 'recurring':
							$message = $view->render('transaction-success-recurring');
						break;
						case 'single':
							$message = $view->render('transaction-success-single');
						break;
					}
					$this->updateTransactionStatus($transaction_id, 'successful');
					return $this->statusMessage($message, 'success');
				} else {
					// Transaction failed
					$this->updateTransactionStatus($transaction_id, 'failed');
					$message = $view->render('transaction-error');
					return $this->statusMessage($message, 'error');
				}
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

	/**
	 * NO LONGER REQUIRED!
	 */
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
	 * NO LONGER REQUIRED!
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
	 * NO LONGER REQUIRED!
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

	/**
	 * Print formatted status message markup
	 * @param  string $message Message text (HTML)
	 * @param  string $type    Type of status message
	 */
	private function statusMessage($message, $type)
	{
		return '<div class="status-wrap status-' . $type . '">
			<div class="status-message">' . $message . '
			<p><a class="new-transaction" href="
			' . strtok($_SERVER["REQUEST_URI"],'?') . '#cnc-donation">Új tranzakció kezdeményezése</a></p>
			</div></div>';
	}

	/**
	 * Get transaction type by reference ID
	 * @param  string $transaction_id Reference ID
	 * @return string                 Type result or NULL
	 */
	private function getTransactionType($transaction_id)
	{
		return $this->db->get_var("SELECT `type` FROM {$this->db_table} 
			WHERE `transaction_id` = '{$transaction_id}'");
	}

	/**
	 * Generate Packages shortcode content
	 * @return string HTML markup of the shortcode
	 */
	public function donationPackagesShortcode()
	{
		wp_enqueue_script('cnc-donation-main');
		$view = new View();
		$html = $view->render('sc-payment-packages');

		$terms = $view->render('terms-' . $this->getCurrentLanguage());
		$view->assign('terms', $terms);

		$view->assign('package_id', 1);
		$view->assign('package_name', __('Ally of Transparency', 'cnc-donation'));
		$html .= $view->render('popup-donation-package');
		$view->assign('package_id', 2);
		$view->assign('package_name', __('Champion of Integrity', 'cnc-donation'));
		$html .= $view->render('popup-donation-package');
		$view->assign('package_id', 3);
		$view->assign('package_name', __('Anti-Corruption Superhero', 'cnc-donation'));
		$html .= $view->render('popup-donation-package');
		return $html;
	}

	/**
	 * Generate Individual payment shortcode content
	 * @return string HTML markup of the shortcode
	 */
	public function donationIndieShortcode()
	{
		wp_enqueue_script('cnc-donation-main');
		$view = new View();

		$terms = $view->render('terms-' . $this->getCurrentLanguage());
		$view->assign('terms', $terms);

		$html = $view->render('sc-payment-indie');
		$view->assign('package_id', 4);
		$view->assign('package_name', __('Unique Donation', 'cnc-donation'));
		$html .= $view->render('popup-donation-indie');
		return $html;
	}

	private function getCurrentLanguage()
	{
		if ( function_exists('icl_object_id') ) {
			$languages = icl_get_languages('skip_missing=1');
			if( !empty( $languages ) ) {
				foreach( $languages as $language ) {
					if( !empty( $language['active'] ) ) {
		                $curr_lang = $language['language_code']; // This will contain current language info.
		                break;
		            }
		        }
		    }
		    return $curr_lang;
		} else {
			return 'hu';
		}
	}

}
