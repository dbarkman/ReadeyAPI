<?php 

/**
 * index.php
 * Description:
 *
 */

session_start();

require_once dirname(__FILE__) . '/../../ReadeyFramework/includes/includes.php';

$readeyAPI = new ReadeyAPI();
$readeyAPI->validateAPICommon();

if (isset($_REQUEST['noun'])) {
	if ($_REQUEST['noun'] === 'testPass') {
		$readeyAPI->testPass();

	} else if ($_REQUEST['noun'] === 'categories') {
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$readeyAPI->getCategories($_REQUEST['uuid']);
		} else {
			$readeyAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'items') {
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			if (isset($_REQUEST['category'])) {
				$readeyAPI->validateGetItems();
				$readeyAPI->getItemsForCategory($_REQUEST['category']);
			} else {
				$readeyAPI->badRequest();
			}
		} else {
			$readeyAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'readLog') {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$readeyAPI->validateReadLog();
			$readLog = array(
				'user' => $_REQUEST['uuid'],
				'words' => $_REQUEST['words'],
				'speed' => $_REQUEST['speed'],
				'rssItemUuid' => $_REQUEST['rssItemUuid']
			);
			$readeyAPI->createReadLog($readLog);
		} else {
			$readeyAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'feedback') {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$readeyAPI->validateFeedback();
			$readLog = array(
				'user' => $_REQUEST['uuid'],
				'feedbackType' => $_REQUEST['feedbackType'],
				'description' => $_REQUEST['description'],
				'email' => $_REQUEST['email']
			);
			$readeyAPI->createFeedback($readLog);
		} else {
			$readeyAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'feedbackConstants') {
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$readeyAPI->getFeedbackConstants();
		} else {
			$readeyAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'example') {
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$readeyAPI->testPass();
		} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$readeyAPI->testPass();
		} else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
			$readeyAPI->testPass();
		} else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
			$readeyAPI->testPass();
		} else {
			$readeyAPI->badRequest();
		}

	} else {
		$readeyAPI->resourceNotFound();
	}
} else {
	$readeyAPI->resourceNotDefined();
}


class ReadeyAPI
{
	private $_uuid;

	private $_timeStamp;
	private $_ip;
	private $_agent;
	private $_language;
	private $_method;

	private $_platform;

	private $_errorCode;
	private $_response;

	private $_page;
	private $_totalPages;
	private $_itemsPerPage;

	private $_start;
	private $_time;
	private $_packageSize;
	private $_size;
	private $_memoryUsage;

	private $_appVersion;
	private $_osVersion;
	private $_device;
	private $_machine;

	private $_logger;
	private $_mySqlConnect;

	private $_validation;

	public function __construct()
	{
		$this->_start = microtime(true);
		$this->_packageSize = null;
		$this->_response = null;
		$this->_responseType = 'json';

		$this->_page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
		$this->_totalPages = 1;
		$this->_itemsPerPage = 10;

		$container = new Container();

		$this->_logger = $container->getLogger();

		$this->_mySqlConnect = $container->getMySqlConnect();

		$this->_validation = $container->getValidation();

		$this->beginRequest();
	}

	private function beginRequest()
	{
		$this->logIt('info', '');
		$this->logIt('info', '--------------------------------------------------------------------------------');
		$this->logIt('info', 'API Session Started');

		$this->_uuid = (isset($_REQUEST['uuid'])) ? $_REQUEST['uuid'] : '';

		$this->_timeStamp = (isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : 'NA');
		$this->_ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'NA');
		$this->_agent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'NA');
		$this->_language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'NA');
		$this->_method = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'NA');

		$this->_platform = 'iOS';
		if (strpos($this->_agent, 'Macintosh') !== FALSE) {
			$this->_platform = 'Mac';
		} else if (strpos($this->_agent, 'Apache') !== FALSE) {
			$this->_platform = 'Android';
		}

		$this->_appVersion = (isset($_REQUEST['appVersion'])) ? $_REQUEST['appVersion'] : '';
		$this->_osVersion = (isset($_REQUEST['osVersion'])) ? $_REQUEST['osVersion'] : '';
		$this->_device = (isset($_REQUEST['device'])) ? $_REQUEST['device'] : '';
		$this->_machine = (isset($_REQUEST['machine'])) ? $_REQUEST['machine'] : '';

		$this->logIt('info', 'TIME: ' . $this->_timeStamp);
		$this->logIt('info', 'IP: ' . $this->_ip);
		$this->logIt('info', 'AGENT: ' . $this->_agent);
		$this->logIt('info', 'LANGUAGE: ' . $this->_language);
		$this->logIt('info', 'METHOD: ' . $this->_method);
		$this->logIt('info', 'NOUN: ' . $_REQUEST['noun']);
	}

	public function logIt($level, $message)
	{
		$this->_logger->$level($message);
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// RESPONSE FUNCTIONS /////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function testPass()
	{
		http_response_code(200);
		$this->echoResponse('none', array(), '', 'success', (object)array());
		$this->completeRequest();
	}

	public function badRequest()
	{
		http_response_code(400);
		$errorCode = 'badRequest';
		$friendlyError = 'Bad Request';
		$errors = array($friendlyError);
		$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		$this->completeRequest();
	}

	public function resourceNotFound()
	{
		http_response_code(404);
		$errorCode = 'resourceNotFound';
		$friendlyError = 'Resource Not Found';
		$errors = array($friendlyError);
		$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		$this->completeRequest();
	}

	public function resourceNotDefined()
	{
		http_response_code(400);
		$errorCode = 'resourceNotDefined';
		$friendlyError = 'Resource Not Defined';
		$errors = array($friendlyError);
		$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// VALIDATION FUNCTIONS ///////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function validateAPICommon()
	{
		$this->_validation->validateAPICommon();
		if ($this->_validation->getErrorCount() > 0) {
			$errorCode = $this->_validation->getErrorCode();
			if ($errorCode == 'invalidParameter') {
				http_response_code(400);
				$this->validationFailed();
			} else if ($errorCode == 'missingParameter') {
				http_response_code(404);
				$this->validationFailed();
			}
		}
	}

	public function validateGetItems()
	{
		$this->_validation->validateGetItems();
		if ($this->_validation->getErrorCount() > 0) {
			$this->validationFailed();
		}
	}

	public function validateReadLog()
	{
		$this->_validation->validateReadLog();
		if ($this->_validation->getErrorCount() > 0) {
			$this->validationFailed();
		}
	}

	public function validateFeedback()
	{
		$this->_validation->validateFeedback();
		if ($this->_validation->getErrorCount() > 0) {
			$this->validationFailed();
		}
	}

	private function validationFailed()
	{
		http_response_code(400);
		$errorCode = $this->_validation->getErrorCode();
		$errors = $this->_validation->getErrors();
		$friendlyError = $this->_validation->getFriendlyError();
		$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// CLEANUP FUNCTIONS //////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function fixMessyItems()
	{
		http_response_code(200);
		$rssItem = new RSSItem($this->_logger, $this->_mySqlConnect->db);
		$items = $rssItem->fixMessyItems();
//		$response['feeds']['data'] = $feeds;
		$response = $items;
		$this->echoResponse('none', array(), '', 'success', $response);
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// FEED FUNCTIONS /////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function getFeeds()
	{
		http_response_code(200);
		$feeds = RSSFeed::GetFeedsForAPI();
//		$response['feeds']['data'] = $feeds;
		$response = $feeds;
		$this->echoResponse('none', array(), '', 'success', $response);
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// CATEGORY FUNCTIONS /////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function getCategories($user)
	{
		http_response_code(200);
		$categories = RSSCategory::GetCategoriesForAPI($user);
		$response = $categories;
		$this->echoResponse('none', array(), '', 'success', $response);
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// ITEM FUNCTIONS /////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function getItemsForFeed($feed)
	{
		http_response_code(200);
		$items = RSSItem::GetItemsForFeedForAPI($feed);
		$response = $items;
		$this->echoResponse('none', array(), '', 'success', $response);
		$this->completeRequest();
	}

	public function getItemsForCategory($category)
	{
		http_response_code(200);
		$items = RSSItem::GetItemsForCategoryForAPI($category, $this->_page, $this->_itemsPerPage, $this->_uuid);
		$this->_totalPages = $this->calculateTotalPages($items[0]);
		array_shift($items);
		$response = $items;
		$this->echoResponse('none', array(), '', 'success', $response);
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// READLOG FUNCTIONS //////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function createReadLog($readLog)
	{
		$date = time();
		$readLogObject = new ReadLog($this->_logger, $this->_mySqlConnect->db);
		$readLogObject->setUuid(UUID::getUUID());
		$readLogObject->setCreated($date);
		$readLogObject->setModified($date);
		$readLogObject->setUser($readLog['user']);
		$readLogObject->setWords($readLog['words']);
		$readLogObject->setSpeed(round($readLog['speed'], 3));
		$readLogObject->setRssItemUuid($readLog['rssItemUuid']);

		if ($readLogObject->createReadLog() === FALSE) {
			http_response_code(500);
			$errorCode = 'readLogNotCreated';
			$friendlyError = 'Read log could not be created.';
			$errors = array($friendlyError);
			$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		} else {
			http_response_code(201);
			$this->echoResponse('none', array(), '', 'success', (object)array());
		}
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// FEEDBACK FUNCTIONS /////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function createFeedback($feedback)
	{
		$date = time();
		$feedbackObject = new Feedback($this->_logger, $this->_mySqlConnect->db);
		$feedbackObject->setUuid(UUID::getUUID());
		$feedbackObject->setCreated($date);
		$feedbackObject->setModified($date);
		$feedbackObject->setUser($feedback['user']);
		$feedbackObject->setEmail($feedback['email']);
		$feedbackObject->setFeedbackType($feedback['feedbackType']);
		$feedbackObject->setDescription($feedback['description']);

		if ($feedbackObject->createFeedback() === FALSE) {
			http_response_code(500);
			$errorCode = 'feedbackNotCreated';
			$friendlyError = 'Feedback could not be created.';
			$errors = array($friendlyError);
			$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		} else {
			http_response_code(201);
			$this->echoResponse('none', array(), '', 'success', (object)array());
		}
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// CONSTANTS API FUNCTIONS ////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function getFeedbackConstants()
	{
		http_response_code(200);

		$feedbackSupportArray = array('An Idea', 'An Issue', 'A Question', 'A Compliment');
		$voteForFeatureArray = array('Create My Own Feed', 'Offline Reading', 'Show Reading Stats', 'Create My Own Articles', 'Other');
		$voteForSourcesArray = array('Pocket', 'Readability', 'Instapaper', 'Dropbox', 'Other');

//		$constants = array(array('feedbackSupportConstants' => $feedbackSupportArray), array('vorForFeaturesConstants' => $voteForFeatureArray), array('votForSourcesConstants' => $voteForSourcesArray));
		$constants = array($feedbackSupportArray, $voteForFeatureArray, $voteForSourcesArray);
		$this->echoResponse('none', array(), '', 'success', $constants);
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// UTILITY FUNCTIONS //////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	private function calculateTotalPages($itemCount)
	{
		$pages = ($itemCount / $this->_itemsPerPage);
		$modulo = ($itemCount % $this->_itemsPerPage);

		if ($modulo != 0) {
			$pages++;
			return $pages;
		}

		return $pages;
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// CLOSING FUNCTIONS //////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	private function echoResponse($errorCode, $errors, $friendlyErrors, $result, $data)
	{
		// if a callback is set, assume jsonp and wrap the response in the callback function
		if (isset($_REQUEST['callback']) && strtolower($_REQUEST['responseType'] === 'jsonp')) {
			echo $_REQUEST['callback'] . '(';
		}

		$this->_errorCode = $errorCode;

		$jsonResponse = array();
		$jsonResponse['httpStatus'] = http_response_code();
		$jsonResponse['noun'] = $_REQUEST['noun'];
		$jsonResponse['verb'] = $_SERVER['REQUEST_METHOD'];
		$jsonResponse['errorCode'] = $errorCode;
		$jsonResponse['errors'] = $errors;
		$jsonResponse['friendlyError'] = $friendlyErrors;
		$jsonResponse['result'] = $result;
		$jsonResponse['count'] = count($data);
		$jsonResponse['page'] = (int)$this->_page;
		$jsonResponse['totalPages'] = (int)$this->_totalPages;
		$jsonResponse['data'] = $data;
		foreach ($errors as $error) {
			$this->logIt('info', $error);
		}
		$this->_response = json_encode($jsonResponse);
		header('Content-type: application/json');
		echo $this->_response;

		if (isset($_REQUEST['callback']) && strtolower($_REQUEST['responseType'] === 'jsonp')) {
			echo ')';
		}
	}

	private function completeRequest()
	{
		$this->_time = (microtime(true) - $this->_start);
		$this->_packageSize = strlen($this->_response);
		$this->_size = number_format($this->_packageSize);
		$this->_memoryUsage = number_format(memory_get_usage());

		$this->logIt('info', 'Payload Time: ' . $this->_time);
		$this->logIt('info', 'Payload Size: ' . $this->_size);
		$this->logIt('info', 'Memory Usage: ' . $this->_memoryUsage);
		$this->logIt('info', 'API Session Ended');
		$this->logIt('info', '--------------------------------------------------------------------------------');
		$this->logIt('info', '');

		$osVersion = '';
		$osAPILevel = '';
		$carrier = '';
		$device = '';
		$display = '';
		$manufacturer = '';
		$model = '';
		if (isset($_REQUEST['androidInfo'])) {
			$androidInfoArray = explode('|',$_REQUEST['androidInfo']);
			$osVersion = $androidInfoArray[0];
			$osAPILevel = $androidInfoArray[1];
			$carrier = $androidInfoArray[2];
			$device = $androidInfoArray[3];
			$display = $androidInfoArray[4];
			$manufacturer = $androidInfoArray[5];
			$model = $androidInfoArray[6];
		}

		$countForRequest = 0;
		$requestForLogging = '';
		if (isset($_REQUEST['key'])) $_REQUEST['key'] = substr($_REQUEST['key'], 0, 8);
		foreach ($_REQUEST as $key => $value) {
			if ($countForRequest > 0) $requestForLogging .= ' - ';
			$value = urldecode($value);
			$requestForLogging .= $key . ': ' . $value;
			$countForRequest++;
		}
		$this->logIt('debug', ' - REQUESTSTRING: ' . $requestForLogging . ' - ' . $this->_agent);

		$logRequestArguments = array(
			'uuid' => $this->_uuid,
			'noun' => $_REQUEST['noun'],
			'verb' => $_SERVER['REQUEST_METHOD'],
			'request' => $requestForLogging,
			'agent' => $this->_agent,
			'timeStamp' => $this->_timeStamp,
			'language' => $this->_language,
			'httpStatus' => http_response_code(),
			'errorCode' => $this->_errorCode,
			'time' => $this->_time,
			'size' => $this->_size,
			'memory' => $this->_memoryUsage,
			'appVersion' => $this->_appVersion,
			'platform' => $this->_platform,
			'device' => urldecode($this->_device),
			'machine' => $this->_machine,
			'osVersion' => $this->_osVersion,
			'ip' => $this->_ip
		);

		LogRequest::LogRequestToDB($logRequestArguments, $this->_mySqlConnect->db);
		exit();
	}
}
