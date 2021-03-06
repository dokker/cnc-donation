<?php
namespace cncDonation;

class CRM {

	private $error = false;
	private $error_msg = '';


	# Enter your domain name , agile email and agile api key
	public function __construct()
	{
		// Call in config
		$cac_donation_config = include(plugin_dir_path(dirname(__FILE__)) . '/config.php');
		$this->agile_domain = $cac_donation_config['agile_domain'];
		$this->agile_user_email = $cac_donation_config['agile_user_email'];
		$this->agile_rest_api_key = $cac_donation_config['agile_rest_api_key'];
	}

	/**
	 * Agile CRM \ Curl Wrap
	 * 
	 * The Curl Wrap is the entry point to all services and actions.
	 *
	 * @author    Agile CRM developers <Ghanshyam>
	 */
	public function curl_wrap($entity, $data, $method, $content_type) {
	    if ($content_type == NULL) {
	        $content_type = "application/json";
	    }

	    $agile_url = "https://" . $this->agile_domain . ".agilecrm.com/dev/api/" . $entity;

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	    curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);
	    switch ($method) {
	        case "POST":
	            $url = $agile_url;
	            curl_setopt($ch, CURLOPT_URL, $url);
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	            break;
	        case "GET":
	            $url = $agile_url;
	            curl_setopt($ch, CURLOPT_URL, $url);
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	            break;
	        case "PUT":
	            $url = $agile_url;
	            curl_setopt($ch, CURLOPT_URL, $url);
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	            break;
	        case "DELETE":
	            $url = $agile_url;
	            curl_setopt($ch, CURLOPT_URL, $url);
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	            break;
	        default:
	            break;
	    }
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        "Content-type : $content_type;", 'Accept : application/json'
	    ));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_USERPWD, $this->agile_user_email . ':' . $this->agile_rest_api_key);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    return $output;
	}

	/**
	 * Get CRM contact by email
	 * @param  string $email Email address
	 * @return string        JSON response data
	 */
	public function getContactByEmail($email)
	{
		$this->error = false;
		$this->error_msg = '';
		$result = $this->curl_wrap("contacts/search/email/" . $email, null, "GET", "application/json");
		return $result;
	}

	/**
	 * Create contact in Agile CRM
	 * @param  array $contact_details	Set of contact details
	 * @return string            REST API Json result
	 */
	public function createContact($contact_details)
	{
		$this->error = false;
		$this->error_msg = '';
		$contact_json = [
			'tags' => $contact_details['tags'],
			'properties' => [
				[
					'name' => 'first_name',
					'value' => $contact_details['first_name'],
					'type' => 'SYSTEM',
				],
				[
					'name' => 'last_name',
					'value' => $contact_details['last_name'],
					'type' => 'SYSTEM',
				],
				[
					'name' => 'email',
					'value' => $contact_details['email'],
					'type' => 'SYSTEM',
				],
				[
					'name' => 'title',
					'value' => 'supporter',
					'type' => 'SYSTEM',
				],
			],
		];
		$contact_json = json_encode($contact_json);
		$result = $this->curl_wrap('contacts', $contact_json, 'POST', 'application/json');
		return $result;
	}

	/**
	 * Fetch contact data from CRM
	 * @param  string $email Email address
	 * @return object        REST API Json result
	 */
	private function fetchContact($email)
	{
		$this->error = false;
		$this->error_msg = '';
		$result = $this->curl_wrap("contacts/search/email/{$email}", null, "GET", "application/json");
		return $result;
	}

	/**
	 * Update contact tags in Agile CRM
	 * @param  array $contact_details	Set of contact details
	 * @param object $saved_contact Json object of saved contact
	 * @return string            REST API Json result
	 */
	private function updateContactTags($contact_details, $saved_contact)
	{
		$contact_json = [
			'id' => $saved_contact->id,
			'tags' => $contact_details['tags'],
		];
		$contact_json = json_encode($contact_json);
		$result = $this->curl_wrap('contacts/edit/tags', $contact_json, 'PUT', 'application/json');
		return $result;
	}

	/**
	 * Insert contact in Agile CRM
	 * @param  string $first_name First name
	 * @param  string $last_name  Last name
	 * @param  string $email      Email address
	 * @param  array $tags        Tags
	 * @return string            REST API Json result
	 */
	public function insertContact($first_name, $last_name, $email, $tags)
	{
		$contact_details = [
			'first_name' => $first_name,
			'last_name' => $last_name,
			'email' => $email,
			'tags' => $tags,
		];
		$fetch_result = $this->fetchContact($email);
		if($this->checkResult($fetch_result)) {
			$crm_result = $this->checkResult($this->updateContactTags($contact_details, json_decode($fetch_result)));
		} else {
			$crm_result = $this->checkResult($this->createContact($contact_details));
		}
	}

	/**
	 * Check string is Json data
	 * @param  string  $string String to check
	 * @return boolean         Check result
	 */
	private function isJson($string) {
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * Check API call result
	 * @param  string $result Result JSON data
	 * @return [type]         [description]
	 */
	public function checkResult($result)
	{
		if ($this->isJson($result)) {
			$response = json_decode($result);
			if (isset($response->exception_message)) {
				$this->error = true;
				$this->error_msg = $response->exception_message;
				return false;
			} else {
				return true;
			}
		} else {
			$this->error = true;
			$this->error_msg = __('Error: ', 'cnc-donation') . $result;
			return false;
		}
	}

	/**
	 * Print last error message
	 * @return string Error message
	 */
	public function getLastError()
	{
		return $this->error_msg;
	}
}
