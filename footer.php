<?php
//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

?>
<!-- break contained floats -->        

                <div style="float:none; clear:both;"></div>

<!-- END MAIN BODY - Close DIV center -->

		</div>
        
        <div id="right"></div>
<!-- break 3-col float -->        
        <div style="float:none; clear:both;"></div>
        
<!-- Close DIV container -->
    </div>
<?php if (!isset($inFeedlist)) { ?>
	<div style="text-align:center;"><a href="feedlist.php" title="Rss & Atom Feeds"><img src="images/rss20.png"></img><img src="images/atom20.png"></img></a></div>
<?php } ?>	

	<div id="footer">
    <p>&copy; <? echo date("Y"); ?> <a href="http://www.lovemachineinc.com" target="_blank">LoveMachine, Inc.</a> &nbsp;| &nbsp;<a href="privacy.php">Privacy Policy</a><br/><a href="http://svn.sendlove.us/" target="_blank">View the source code</a></p>
    <img src="images/LMLogo3.png" />
    </div>

<!-- Close DIV outside -->
</div id="outside">

<!-- Google Analytics -->
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try{ 
var pageTracker = _gat._getTracker("UA-11529958-3");
pageTracker._trackPageview();
} catch(err) {} 
</script>

</body>
</html>
