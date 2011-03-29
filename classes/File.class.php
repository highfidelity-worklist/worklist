<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

//  This class handles one or more Files if you need more functionality don't hesitate to add it.
//  But please be as fair as you comment your methods - maybe another developer needs them too.

class File
{
	
	protected $id;
	protected $userid;
	protected $workitem;
	protected $mime;
	protected $title;
	protected $description;
	protected $url;
	protected $status;
	protected $files = array();
	
    /**
     * With this constructor you can create a user by passing an array.
     *
     * @param array $options
     * @return User $this
     */
    public function __construct(array $options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
        return $this;
    }

    /**
     * This method finds a file by its id
     * 
     * @param int $id
     * @return File $this
     */
    public function findFileById($id = null)
    {
    	if (null === $id) {
    		throw new Exception('Now file id defined!');
    	}
    	$where = sprintf('`id` = %d', (int)$id);
		return $this->loadFiles($where);
    }
    
	/**
	 * @return the $id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param $id the $id to set
	 */
	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	/**
	 * @return the $userid
	 */
	public function getUserid() {
		return $this->userid;
	}

	/**
	 * @param $workitem the $userid to set
	 */
	public function setUserid($userid) {
		$this->userid = $userid;
		return $this;
	}
	
	/**
	 * @return the $workitem
	 */
	public function getWorkitem() {
		return $this->workitem;
	}

	/**
	 * @param $workitem the $workitem to set
	 */
	public function setWorkitem($workitem) {
		$this->workitem = $workitem;
		return $this;
	}

	/**
	 * @return the $mime
	 */
	public function getMime() {
		return $this->mime;
	}

	/**
	 * @param $mime the $mime to set
	 */
	public function setMime($mime) {
		$this->mime = $mime;
		return $this;
	}

	/**
	 * @return the $title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param $title the $title to set
	 */
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	/**
	 * @return the $description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @param $description the $description to set
	 */
	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	/**
	 * @return the $url
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @param $url the $url to set
	 */
	public function setUrl($url) {
		$this->url = $url;
		return $this;
	}

	/**
	 * @return the $status
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param $status the $status to set
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

    /**
     * @return the $files
     */
	public function getFiles() {
		return $this->files;
	}

    /**
     * Checks if the setter for the property exists and calls it
     *
     * @param string $name Name of the property
     * @param string $value Value of the property
     * @throws Exception
     * @return void
     */
    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid ' . __CLASS__ . ' property');
        }
        $this->$method($value);
    }

    /**
     * Checks if the getter for the property exists and calls it
     *
     * @param string $name Name of the property
     * @param string $value Value of the property
     * @throws Exception
     * @return void
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new Exception('Invalid ' . __CLASS__ . ' property');
        }
        $this->$method();
    }
	
	/**
	 * Use this method to update or insert a user.
	 * 
	 * @return (boolean)
	 */
	public function save()
	{
		if (null === $this->getId()) {
			$id = $this->insert();
			if ($id !== false) {
				$this->setId($id);
				return true;
			}
			return false;
		} else {
			return $this->update();
		}
	}
	
    /**
     * Automatically sets the options array
     * Array: Name => Value
     *
     * @param array $options
     * @return User $this
     */
	private function setOptions(array $options)
	{
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
	}
	
	private function loadFiles($where)
	{
		// now we build the sql query
		$sql = 'SELECT * FROM `' . FILES . '` WHERE ' . $where . ' LIMIT 1;';
		// and get the result
		$result = mysql_query($sql);
		
		if ($result && (mysql_num_rows($result) == 1)) {
			$options = mysql_fetch_assoc($result);
			$this->setOptions($options);
			return $this;
		}
		return false;
	}
	
	private function getColumns()
	{
		$columns = array();
		$result = mysql_query('SHOW COLUMNS FROM `' . FILES);
		if (mysql_num_rows($result) > 0) {
		    while ($row = mysql_fetch_assoc($result)) {
		        $columns[] = $row;
		    }
			return $columns;
		}
		return false;
	}
	
	private function prepareData()
	{
		$columns = $this->getColumns();
		$cols = array(); $values = array();
		foreach ($columns as $col) {
			$method = 'get' . ucfirst($col['Field']);
			if (method_exists($this, $method) && (null !== $this->$method())) {
				$cols[] = $col['Field'];
				if (preg_match('/(char|text|blob)/i', $col['Type']) === 1) {
					$values[] = mysql_real_escape_string($this->$method());
				} else {
					$values[] = $this->$method();
				}
			}
		}
		return array(
			'columns' => $cols,
			'values' => $values
		);
	}

	private function insert()
	{
		$data = $this->prepareData();
		$sql = 'INSERT INTO `' . FILES . '` (`' . implode('`,`', $data['columns']) . '`) VALUES ("' . implode('","', $data['values']) . '")';
		$result = mysql_query($sql);
		if ($result) {
			return mysql_insert_id();
		}
		return false;
	}
	
	private function update()
	{
		$flag = false;
		$data = $this->prepareData();
		$sql = 'UPDATE `' . FILES . '` SET ';
		foreach ($data['columns'] as $index => $column) {
			if ($column == 'id') {
				continue;
			}
			if ($flag === true) {
				$sql .= ', ';
			}
			$sql .= '`' . $column . '` = "' . $data['values'][$index] . '"';
			$flag = true;
		}
		$sql .= ' WHERE `id` = ' . (int)$this->getId() . ';'; 
		$result = mysql_query($sql);
		if ($result) {
			return true;
		}
		return false;
	}
	
	public function remove()
	{
		$flag = false;
		$sql = 'DELETE FROM `' . FILES . '` ';
		$sql .= ' WHERE `id` = ' . (int)$this->getId() . ';'; 
		$result = mysql_query($sql);
		if ($result) {
            return array(
                'success' => true,
                'message' => "File removed:" . (int)$this->getId()
            );
		}
        return array(
            'success' => false,
            'message' => "Error, cannot remove file :" . (int)$this->getId() . " , sql:" . $sql
        );
	}
	
	public static function uniqueFilename($strExt = 'tmp')
	{
		// explode the IP of the remote client into four parts
		$arrIp = explode('.', $_SERVER['REMOTE_ADDR']);
		// get both seconds and microseconds parts of the time
		list($usec, $sec) = explode(' ', microtime());
		// fudge the time we just got to create two 16 bit words
		$usec = (integer) ($usec * 65536);
		$sec = ((integer) $sec) & 0xFFFF;
		// fun bit--convert the remote client's IP into a 32 bit
		// hex number then tag on the time.
		// Result of this operation looks like this xxxxxxxx-xxxx-xxxx
		$strUid = sprintf("%08x-%04x-%04x", ($arrIp[0] << 24) | ($arrIp[1] << 16) | ($arrIp[2] << 8) | $arrIp[3], $sec, $usec);
		// tack on the extension and return the filename
		return $strUid . '.' . $strExt;
	}
	
	public static function isAllowed($filestatus = 3, $user = null)
	{
		if (null === $user) {
			return false;
		}
		
		switch ($filestatus) {
			case 3:
				if ($user->isRunner && $user->isPayer) {
					return true;
				}
				break;
			case 2:
				if ($user->isRunner) {
					return true;
				}
				break;
			case 1:
				if ($user->isPayer) {
					return true;
				}
				break;
			case 0:
				return true;
				break;
		}
		return false;
	}
	
    
    /**
     * This method fetches all files for a workitem
     * 
     * @param int $workitem
     * @return array $files
     */
    public static function fetchAllFilesForWorkitem($workitem = null)
    {
    	if (null === $workitem) {
    		throw new Exception('You have to define a workitem!');
    	}
    	$sql = 'SELECT `id` FROM `' . FILES . '` WHERE `workitem` = ' . (int)$workitem;
    	$result = mysql_query($sql);
    	$files = array();
    	while ($row = mysql_fetch_assoc($result)) {
    		$file = new File();
    		$file->findFileById($row['id']);
    		$files[] = $file;
    	}
    	return $files;
    }
    
    /**
     * This method fetches all files for a project
     * 
     * @param int $projectid
     * @return array $files
     */
    public static function fetchAllFilesForProject($projectid = null)
    {
    	if (null === $projectid) {
    		throw new Exception('You have to define a projectid!');
    	}
    	$sql = 'SELECT `id` FROM `' . FILES . '` WHERE `workitem` IS NULL and `projectid`='.(int)$projectid;
    	$result = mysql_query($sql);
    	$files = array();
    	while ($row = mysql_fetch_assoc($result)) {
    		$file = new File();
    		$file->findFileById($row['id']);
    		$files[] = $file;
    	}
    	return $files;
    }
	
	public static function fileUploadErrorMessage($error_code) {
		switch ($error_code) {
		    case UPLOAD_ERR_INI_SIZE:
		        return 'The uploaded file exceeds the max filesize of 2 MB.';
		    case UPLOAD_ERR_FORM_SIZE:
		        return 'The uploaded file exceeds the max filesize of 2 MB.';
		    case UPLOAD_ERR_PARTIAL:
		        return 'The uploaded file was only partially uploaded, please try again later.';
		    case UPLOAD_ERR_NO_FILE:
		        return 'No file was uploaded.';
		    case UPLOAD_ERR_NO_TMP_DIR:
		        return 'Missing a temporary folder, please try again later.';
		    case UPLOAD_ERR_CANT_WRITE:
		        return 'Failed to write file to disk, please try again later.';
		    case UPLOAD_ERR_EXTENSION:
		        return 'File upload stopped by extension, please try again later.';
		    default:
		        return 'Unknown upload error, please try again later.';
		}
	} 
	
	public static function getIconFromMime($mime = '')
	{
		$mimes = array(
			'application/pdf' 			=> 'images/icons/pdf.png',
			'application/x-download'	=> 'images/icons/pdf.png',
			'text/plain'				=> 'images/icons/txt.png',
			'text/rtf'					=> 'images/icons/rtf.png',
			'image/jpeg'				=> false,
			'image/pjpeg'				=> false,
			'image/gif'					=> false,
			'image/png'					=> false,
			'image/x-png'				=> false
		);
		
		if (isset($mimes[$mime])) {
			return $mimes[$mime];
		}
		return 'images/icons/default.png';
		
//		$mimes = array(	'hqx'	=>	'application/mac-binhex40',
//				'cpt'	=>	'application/mac-compactpro',
//				'csv'	=>	array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
//				'bin'	=>	'application/macbinary',
//				'dms'	=>	'application/octet-stream',
//				'lha'	=>	'application/octet-stream',
//				'lzh'	=>	'application/octet-stream',
//				'exe'	=>	array('application/octet-stream', 'application/x-msdownload'),
//				'class'	=>	'application/octet-stream',
//				'psd'	=>	'application/x-photoshop',
//				'so'	=>	'application/octet-stream',
//				'sea'	=>	'application/octet-stream',
//				'dll'	=>	'application/octet-stream',
//				'oda'	=>	'application/oda',
//				'pdf'	=>	array('application/pdf', 'application/x-download'),
//				'ai'	=>	'application/postscript',
//				'eps'	=>	'application/postscript',
//				'ps'	=>	'application/postscript',
//				'smi'	=>	'application/smil',
//				'smil'	=>	'application/smil',
//				'mif'	=>	'application/vnd.mif',
//				'xls'	=>	array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
//				'ppt'	=>	array('application/powerpoint', 'application/vnd.ms-powerpoint'),
//				'wbxml'	=>	'application/wbxml',
//				'wmlc'	=>	'application/wmlc',
//				'dcr'	=>	'application/x-director',
//				'dir'	=>	'application/x-director',
//				'dxr'	=>	'application/x-director',
//				'dvi'	=>	'application/x-dvi',
//				'gtar'	=>	'application/x-gtar',
//				'gz'	=>	'application/x-gzip',
//				'php'	=>	'application/x-httpd-php',
//				'php4'	=>	'application/x-httpd-php',
//				'php3'	=>	'application/x-httpd-php',
//				'phtml'	=>	'application/x-httpd-php',
//				'phps'	=>	'application/x-httpd-php-source',
//				'js'	=>	'application/x-javascript',
//				'swf'	=>	'application/x-shockwave-flash',
//				'sit'	=>	'application/x-stuffit',
//				'tar'	=>	'application/x-tar',
//				'tgz'	=>	array('application/x-tar', 'application/x-gzip-compressed'),
//				'xhtml'	=>	'application/xhtml+xml',
//				'xht'	=>	'application/xhtml+xml',
//				'zip'	=>  array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
//				'mid'	=>	'audio/midi',
//				'midi'	=>	'audio/midi',
//				'mpga'	=>	'audio/mpeg',
//				'mp2'	=>	'audio/mpeg',
//				'mp3'	=>	array('audio/mpeg', 'audio/mpg'),
//				'aif'	=>	'audio/x-aiff',
//				'aiff'	=>	'audio/x-aiff',
//				'aifc'	=>	'audio/x-aiff',
//				'ram'	=>	'audio/x-pn-realaudio',
//				'rm'	=>	'audio/x-pn-realaudio',
//				'rpm'	=>	'audio/x-pn-realaudio-plugin',
//				'ra'	=>	'audio/x-realaudio',
//				'rv'	=>	'video/vnd.rn-realvideo',
//				'wav'	=>	'audio/x-wav',
//				'bmp'	=>	'image/bmp',
//				'gif'	=>	'image/gif',
//				'jpeg'	=>	array('image/jpeg', 'image/pjpeg'),
//				'jpg'	=>	array('image/jpeg', 'image/pjpeg'),
//				'jpe'	=>	array('image/jpeg', 'image/pjpeg'),
//				'png'	=>	array('image/png',  'image/x-png'),
//				'tiff'	=>	'image/tiff',
//				'tif'	=>	'image/tiff',
//				'css'	=>	'text/css',
//				'html'	=>	'text/html',
//				'htm'	=>	'text/html',
//				'shtml'	=>	'text/html',
//				'txt'	=>	'text/plain',
//				'text'	=>	'text/plain',
//				'log'	=>	array('text/plain', 'text/x-log'),
//				'rtx'	=>	'text/richtext',
//				'rtf'	=>	'text/rtf',
//				'xml'	=>	'text/xml',
//				'xsl'	=>	'text/xml',
//				'mpeg'	=>	'video/mpeg',
//				'mpg'	=>	'video/mpeg',
//				'mpe'	=>	'video/mpeg',
//				'qt'	=>	'video/quicktime',
//				'mov'	=>	'video/quicktime',
//				'avi'	=>	'video/x-msvideo',
//				'movie'	=>	'video/x-sgi-movie',
//				'doc'	=>	'application/msword',
//				'docx'	=>	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//				'xlsx'	=>	'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
//				'word'	=>	array('application/msword', 'application/octet-stream'),
//				'xl'	=>	'application/excel',
//				'eml'	=>	'message/rfc822'
//			);
		
	}
	
}
