<?php

/***************************************************************************************
        automatic redirect to a work list or other item
        
        With one small modification this can be used to redirect to anything anywhere
        
        Goal:  convert this   http://dev.sendlove.us/rw/?10948
           into this:  http://dev.sendlove.us/worklist/workitem.php?job_id=10948&action=view
        
        See:  http://dev.sendlove.us/worklist/workitem.php?job_id=10948
        
        
         To use this program create a folder called r at the top level of the site
         Place this file in that folder and name it index.php
         
         Now any references to site/rw/?12345  will automatically be forwarded to
         The WorkList item with the same id #
        
        2010-04-04  Created by CodeHappy  (eriksjobs@emerald-forest.net)

/***************************************************************************************/

//These globals define the location of the worklist program
//Note: Don't put a leading slash on the path, it should be relative to the domain
//
//Security Consideration: These MUST BE Trusted Paths

//path to a worklist item, it is the responsiblity of the receiving page to deal with invalid id's
if (empty($ReDirItemPath)) {
	$ReDirItemPath = 'workitem.php';
}

//where to go when id not specified: points to main index
if (empty($ReDirErrorPath)) {
	$ReDirErrorPath = 'worklist.php';
}



//***********************************************************
function GetServer()
{
        //deal with alternate ports and ssl
        if (empty($_SERVER['HTTPS']))
        {
            $prefix	= 'http://';
            $port	= ((int)$_SERVER['SERVER_PORT'] == 80) ? '' :  ":{$_SERVER['SERVER_PORT']}";
        }
        else
        {
            $prefix	= 'https://';
            $port	= ((int)$_SERVER['SERVER_PORT'] == 443) ? '' :  ":{$_SERVER['SERVER_PORT']}";
        }

        //Get the URL, split it up by "/" and return it without the last 2 sections (redirect folder name & filename)
        $full_path			= $_SERVER['REQUEST_URI'];
        $full_path_split	= explode('/', $full_path);
        $full_path_split[count($full_path_split)-1] = null;
        $full_path_split[count($full_path_split)-2] = null;
        foreach($full_path_split as $index => $data) {
        	if(empty($data)) {
        		unset($full_path_split[$index]);
        	}
        }
        $path_dirs = implode('/', $full_path_split);
        
        $full_url = $prefix . $_SERVER['HTTP_HOST'] . $port . '/' . $path_dirs;

        return $full_url;
}

//***********************************************************


function IdToURL($id, $ItemPath, $ErrorPath)
{
    $baseurl = GetServer();

    if (!empty($id))
    {
        // == server/worklist/workitem.php?job_id=10948&action=view
        $redir = "$baseurl/$ItemPath?job_id=$id&action=view";
    }
    else
    {
        //The id is missing, so default action is to redirect to the main page
        $redir = "$baseurl/$ErrorPath";
    }
    
    return $redir;
}


//***********************************************************
//if the browser does not support 'refresh' we use javascript
//if javascript is blocked we give them a manual click option

function MakeWebOut($id, $url)
{

if (empty($id)) {
	$id = '(missing)';
}
    
$out = <<<EOD
<html>
<head>
<meta http-equiv="refresh" content="0; url=$url" />
<script>
    window.location.href = "$url";
</script>    
</head>
<body>
This page should automatically redirect to <a href="$url">Item # $id</a><br />
If it does not redirect, <a href="$url">click here</a> to continue.
</body>
</html>
EOD;

    return $out;
}

//***********************************************************

//Security Sanitize:
$id = (empty($_SERVER['QUERY_STRING'])) ? false : (int) $_SERVER['QUERY_STRING'];
$url = IdToURL($id, $ReDirItemPath, $ReDirErrorPath);
echo MakeWebOut($id, $url);
?>
