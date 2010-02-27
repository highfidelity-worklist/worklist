<?php 
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

//  This class handles a Json Requests if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your methods - maybe another developer needs them too.

class JsonServer
{
	
	protected $output;
	protected $user;
	protected $request;
	
	/**
	 * If an action is passed and the method exists it will call this method.
	 * 
	 * @param (string) $action
	 * @param (mixed) $arguments
	 */
	public function __construct()
	{

	}
	
	/**
	 * Get the output
	 *
	 * @return (string) $this->output
	 */	
	public function getOutput()
	{
		if (null === $this->output) {
			$this->setOutput(array(
				'success' => false,
				'message' => 'No output!'
			));
		}
		return $this->output;
	}
	
	/**
	 * Sets the output property and json_encodes it.
	 * 
	 * @param (array) $output
	 * @return JsonServer $this
	 */
	public function setOutput(array $output)
	{
		$this->output = json_encode($output);
	}
	
	public function run()
	{
		$method = 'action' . ucfirst($this->getAction());
		if (!method_exists($this, $method)) {
			throw new Exception('Action does not exit!');
		}
		
		$this->$method();
	}
	
	/**
	 * @return the $action
	 */
	public function getAction()
	{
		return $this->getRequest()->getActionName();
	}

	/**
	 * @return the $user
	 */
	public function getUser()
	{
		if (null === $this->user) {
			$this->setUser();
		}
		return $this->user;
	}

	/**
	 * @return the $request
	 */
	public function getRequest()
	{
		if (null === $this->request) {
			$this->setRequest();
		}
		return $this->request;
	}

	/**
	 * This method gets the active user
	 */
	public function setUser()
	{
		$user = new User();
		$user->findUserById($_SESSION['userid']);
		$this->user = $user;
	}

	/**
	 * Here we set the JsonServer_Request
	 */
	public function setRequest()
	{
		$this->request = new JsonServer_Request();
	}

	/**
	 * This method approves a user
	 * 
	 * @param (array) $args
	 */
	protected function actionApproveUser($args = null)
	{
		if (null === $this->getRequest()->getParam('userid')) {
			throw new Exception('User ID not set!');
		}

		if ($this->getUser()->isRunner()) {
			$user = new User();
			$user->findUserById($this->getRequest()->getParam('userid'));
			$user->setHas_w9approval(1);

			if ($user->save()) {
				return $this->setOutput(array(
					'success' => true,
					'message' => 'The user ' . $user->getNickname() . ' has been approved!'
				));
			} else {
				return $this->setOutput(array(
					'success' => false,
					'message' => 'Something went wrong, try it again later.'
				));
			}
		} else {
			return $this->setOutput(array(
				'success' => false,
				'message' => 'You are not allowed to do that!'
			));
		}
	}
	
	/**
	 * This method checks the approval status of a user
	 */
	protected function actionApprovalStatus()
	{
		if (null === $this->getRequest()->getParam('userid')) {
			throw new Exception('User ID not set!');
		}
		$user = new User();
		$user->findUserById($this->getRequest()->getParam('userid'));
		
		if ($user->isW9Approved()) {
			return $this->setOutput(array(
				'success' => true,
				'approved'=> true,
				'message' => 'The user ' . $user->getNickname() . ' is approved!'
			));
		} else {
			return $this->setOutput(array(
				'success' => true,
				'approved'=> false,
				'message' => 'The user ' . $user->getNickname() . ' is not approved!'
			));
		}
	}
	
	/**
	 * This method checks the approval status of a user
	 */
	protected function actionIsUSCitizen()
	{
		if (null === $this->getRequest()->getParam('userid')) {
			throw new Exception('User ID not set!');
		}
		$user = new User();
		$user->findUserById($this->getRequest()->getParam('userid'));
		
		if ($user->isUsCitizen()) {
			return $this->setOutput(array(
				'success' => true,
				'isuscitizen'=> true,
				'message' => 'The user ' . $user->getNickname() . ' is an US citizen!'
			));
		} else {
			return $this->setOutput(array(
				'success' => true,
				'isuscitizen'=> false,
				'message' => 'The user ' . $user->getNickname() . ' is not an US citizen!'
			));
		}
	}
	
	/**
	 * This method adds a file to a workitem
	 */
	protected function actionFileUpload()
	{
		// check if we have a file
		if (empty($_FILES)) {
			return $this->setOutput(array(
				'success' => false,
				'message' => 'No file uploaded!'
			));
		}
		
		$mime = $_FILES['file']['type'];
		$ext = end(explode(".", $_FILES['file']['name']));
		$fileName = File::uniqueFilename($ext);
		$tempFile = $_FILES['file']['tmp_name'];
		$title = basename($_FILES['file']['name']);
		$path = UPLOAD_PATH . '/' . $fileName;
		
		if (move_uploaded_file($tempFile, $path)) {
			$url = SERVER_URL . 'uploads/' . $fileName;
			$workitem = $this->getRequest()->getParam('workitem');
			$workitem = (is_numeric($workitem) ? $workitem : null);
			
			$file = new File();
			$file->setMime($mime)
				 ->setUserid($this->getRequest()->getParam('userid'))
				 ->setWorkitem($workitem)
				 ->setTitle($title)
				 ->setUrl($url);
			$success = $file->save();
			
			$icon = File::getIconFromMime($file->getMime());
			if ($icon === false) {
				$filetype = 'image';
				$icon = $file->getUrl();
			}
			
			return $this->setOutput(array(
				'success' => $success,
				'fileid'  => $file->getId(),
				'url' 	  => $file->getUrl(),
				'icon'	  => $icon,
				'title'	  => $file->getTitle(),
				'description' => '',
				'filetype'=> (isset($filetype) ? $filetype : '')
			));
		} else {
			return $this->setOutput(array(
				'success' => false,
				'message' => 'An error occured while uploading the file, please try again!'
			));
		}
		
	}
	
	protected function actionChangeFileTitle()
	{
		$fileid = $this->getRequest()->getParam('fileid');
		$title = $this->getRequest()->getParam('value');
		
		$file = new File();
		$file->findFileById($fileid);
		$file->setTitle((string)$title);
		$success = $file->save();
		
		die($title);
	}
	
	protected function actionChangeFileDescription()
	{
		$fileid = $this->getRequest()->getParam('fileid');
		$description = $this->getRequest()->getParam('value');
		
		$file = new File();
		$file->findFileById($fileid);
		$file->setDescription((string)$description);
		$success = $file->save();
		
		die($description);
	}
	
	protected function actionChangeFileStatus()
	{
		$fileid = $this->getRequest()->getParam('fileid');
		$status = $this->getRequest()->getParam('status');
		
		$file = new File();
		$file->findFileById($fileid);
		$file->setStatus((int)$status);
		$success = $file->save();
		
		return $this->setOutput(array(
			'success' => $success
		));
	}
	
	protected function actionGetFilesForWorkitem()
	{
		$files = File::fetchAllFilesForWorkitem($this->getRequest()->getParam('workitem'));
		$user = new User();
		$user->findUserById($this->getRequest()->getParam('userid'));
		$data = array(
			'images' => array(),
			'documents' => array()
		);
		foreach ($files as $file) {
			if (!File::isAllowed($file->getStatus(), $user)) {
				continue;
			}
			$icon = File::getIconFromMime($file->getMime());
			if ($icon === false) {
				array_push($data['images'], array(
					'fileid'=> $file->getId(),
					'url'	=> $file->getUrl(),
					'icon'	=> $file->getUrl(),
					'title' => $file->getTitle(),
					'description' => $file->getDescription()
				));
			} else {
				array_push($data['documents'], array(
					'fileid'=> $file->getId(),
					'url'	=> $file->getUrl(),
					'icon'	=> $icon,
					'title' => $file->getTitle(),
					'description' => $file->getDescription()
				));
			}
		}
		
		return $this->setOutput(array(
			'success' => true,
			'data' => $data
		));
	}

	/**
	 * This method handles the upload of the W9 form
	 *
	 */
	protected function actionW9Upload()
	{
		// check if we have a file
		if (empty($_FILES)) {
			return $this->setOutput(array(
				'success' => false,
				'message' => 'No file uploaded!'
			));
		}
		
		$tempFile = $_FILES['Filedata']['tmp_name'];
		$path = UPLOAD_PATH . '/' . $this->getRequest()->getParam('userid') . '_W9.pdf';
		
		if (move_uploaded_file($tempFile, $path)) {
			$user = new User();
			$user->findUserById($this->getRequest()->getParam('userid'));
			$subject = "W-9 Form from " . $user->getNickname();
			$body = "<p>Hi there,</p>";
			$body .= "<p>" . $user->getNickname() . " just uploaded his/her W-9 Form you can download and approve it from this URL:</p>";
			$body .= "<p><a href=\"" . SERVER_URL . "uploads/" . $user->getId() . "_W9.pdf\">Click here</a></p>";
			$body .= "<p>Love,<br/>Worklist</p>";
			
			$sandy = new User();
			$sandy->findUserByNickname('Sandy');
			sl_send_email($sandy->getUsername() . ', finance@lovemachineinc.com', $subject, $body);
			
			return $this->setOutput(array(
				'success' => true,
				'message' => 'The file ' . basename( $_FILES['Filedata']['name']) . ' has been uploaded.'
			));
		} else {
			return $this->setOutput(array(
				'success' => false,
				'message' => 'An error occured while uploading the file, please try again!'
			));
		}
	}
	
	/**
	 * This method checks if the user is allowed to bid in the W9 context
	 */
	protected function actionCheckUserForW9()
	{
		$user = new User();
		$user->findUserById($this->getRequest()->getParam('userid'));
		
		// If user is no US citizen we don't need the 10099
		if (!$user->isUsCitizen() || $user->isW9Approved()) {
			return $this->setOutput(array(
				'success' => true,
				'message' => 'The user ' . $user->getNickname() . ' is not a US Citizen or has been approved earlier!'
			));
		}

		// Now we need to get the fee amount
		$sql	 = 	'SELECT SUM(`amount`) AS `sum_amount` FROM `' . FEES . '` WHERE ';
		// Get the right userfees
		$sql	.=	'`user_id` = ' . $user->getId() . ' ';
		// Only fees that haven't been withdrawn
		$sql	.=	'AND `withdrawn` = 0 ';
		// Status should be DONE
		$sql	.=	'AND `worklist_id` IN (SELECT `id` FROM `worklist` WHERE `status` = "DONE") ';
		// We only need this year
		$sql	.=	'AND YEAR(`date`) = YEAR(NOW()) ';

		// now we fetch the sum
		$result = mysql_query($sql);
		$fees = mysql_fetch_object($result)->sum_amount;
		if (!is_numeric($fees)) {
			return $this->setOutput(array(
				'success' => true,
				'message' => 'The users ' . $user->getNickname() . ' fees are not numeric, which means he has no paid fees.'
			));
		} else if (((int)$fees + (int)$this->getRequest()->getParam('amount')) < 600) {
			return $this->setOutput(array(
				'success' => true,
				'message' => 'The users ' . $user->getNickname() . ' amount ($' . ((int)$fees + (int)$this->getRequest()->getParam('amount')) . ') does not exceed $600.'
			));
		}

		return $this->setOutput(array(
			'success' => false,
			'message' => 'The users ' . $user->getNickname() . ' amount ($' . ((int)$fees + (int)$this->getRequest()->getParam('amount')) . ') does exceed $600.'
		));
	}
}
