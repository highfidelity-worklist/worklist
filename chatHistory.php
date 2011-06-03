<?php include dirname(__FILE__).'/config.php'; ?>
<script language="JavaScript" src="js/jquery-1.5.2.min.js"></script>
<script language="JavaScript" src="js/jquery.scrollTo-min.js"></script>

<style>
#journalView {
    overflow:auto;
    font-size:16px;
    height:95%;
}
.entry {
    font-family: helvetica, arial, sans-serif;
    font-size: .75em;
    margin: 0 0 0.25em 0.25em;
    padding-right:1em;
}
.entry h2 {
        height:4px;
        margin-top:10px;
        background-color:#ccc;
        color: #444;
    font-size: 1em;
    font-weight: bold;
    padding: 0;
}
.entry h2.sendlove {
}
.entry h2.extra {
        height:4px;
        margin-top:10px;
        background-color:#ccc;
        font-size: .75em;
}
.entry h2.other {
        height:4px;
        margin-top:10px;
        background-color:#ccc6b0;
    color: #4D4B42;
}
.entry h2.other.extra {
        height:4px;
        margin-top:10px;
        background-color:#E5E5E5;
}
.entry h2 span, #online-users span{
    background-color: #CCC;
        -webkit-border-bottom-right-radius: 9px;
        -webkit-border-bottom-left-radius: 9px;
        -moz-border-radius-bottomright: 9px;
        -moz-border-radius-bottomleft: 9px;
        border-bottom-right-radius: 9px;
        border-bottom-left-radius: 9px;
    padding: .25em .75em .125em;
}
.entry h2.extra span {
    background-color: #E5E5E5;
}
.entry h2.other span {
    background-color: #CCC6B0;
}
.entry h2.extra.other span {
    background-color: #E6DFC6;
}
.entry.sendlove h2 {
        height:4px;
        margin-top:10px;
        background-color:#D24B3F;
        color: #601710;
}
.entry.sendlove h2 span {
    background-color: #D24B3F;
}
.entry.journal h2 {
        height:4px;
        margin-top:10px;
        background-color:#89D23F;
        color: #4F4F28;
}
.entry.journal h2 span {
    background-color: #89D23F;
}
.entry.svn h2 {
        height:4px;
        margin-top:10px;
        background-color:#DBDB6E;
        color: #4F4F28;
}
.entry.svn h2 span {
    background-color: #DBDB6E;
}
.entry.worklist h2 {
        height:4px;
        margin-top:10px;
        background-color:#5488D1;
    color: #1F3C66;
}
.entry.worklist h2 span {
    background-color: #5488D1;
}
.entry.sitescan h2 {
        height:4px;
        margin-top:10px;
        background-color:#FFFFFF;
        color:#FFFF00;
        text-decoration:blink;
        background-image:url(../images/alert_bar.gif);
}
.entry.sitescan h2 span {
        background-color:#FF0000;
}
.entry.private {
    margin-left: 42px;
}
.entry.bot h2 {
        height:4px;
        margin-top:10px;
        background-color:#D98D4B;
        color: #54371D;
}
.entry.bot h2 span {
    background-color: #D98D4B;
}
.entry-author {
    float:left;
}
.entry-date {
    cursor: pointer;
    float:right;
}
h2.extra .entry-date-extra {
    font-size: .75em;
}
.entry-text {
    clear: both;
    padding:0 49px;
    line-height: 1.25;
}
.entry a {
    color: #0076fc;
}
#tooltip .tip-entry{
  padding: 0.5em 0 0 0;
  margin: 0.5em 0 0 0;
  border-top: solid 1px;
}
#guest-list .guest-entry{
  border: solid 1px #ccc;
  padding: 0.3em;
  margin-bottom: 0.5em;
  cursor: pointer;
  -moz-border-radius: 0.5em;
  -webkit-border-radius: 0.5em;
}
#guest-list .guest-entry:hover {
  background-color: #FFCC99;
}
</style>

<!--[if IE 6]>
  <link rel="stylesheet" href="//<?php echo SERVER_NAME ?>/journal/css/ie.css" type="text/css" media="all" />
<![endif]-->

<div id="journalView" class="chatHistory">
<?php

$options = array( 
	CURLOPT_URL	       => JOURNAL_QUERY_URL_SSL,
       	CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
       	CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING       => "",
       	CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
       	CURLOPT_TIMEOUT        => 120,
        CURLOPT_MAXREDIRS      => 10,
       	CURLOPT_POST           => 1,
        CURLOPT_POSTFIELDS     => http_build_query(array_merge($_GET,array('api_key' => JOURNAL_API_KEY))),
       	CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => false,
       	CURLOPT_VERBOSE        => 1,
    ); 

$ch = curl_init();
curl_setopt_array($ch,$options);
echo curl_exec($ch);
curl_close($ch);

?>
 <br/>
 <br/>
 <a name="bottomArea" id="bottomArea"></a>
</div>
<a name="realBottomArea" id="realBottomArea"></a>
<script>
  $('#journalView').scrollTop(99999);
</script>
