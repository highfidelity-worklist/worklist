<?php

$tooltips = array(				  
/************************
*	Worklist tooltips   *
************************/
					  
	/* List page top tooltips 		*/
	
	'menuWorklist'	=> 'Explore jobs or add new ones.',
	'menuJournal'	=> 'Live Chat',
	'menuLove'		=> 'Show appreciation for cool things people have done',
	'menuRewarder'	=> 'Give earned points/money to other teammates.',
	'menuReports'	=> 'Audit all the work and money flow.',
	'jobsBidding'	=> 'See more stats on jobs and team members.',
	
	
	/* List's list tooltips 		*/
	
	'addButton'		=> 'Create a new job that needs to get done.',
	'hoverJobRow'	=> 'View, edit, or make a bid on this job.',
	

	/* Item tooltips 				*/ 
	
	'addFee'	=> 'Add a fee you would like to be paid for work done on this job.',
	'addBid'	=> 'Make an offer to do this job.'

/*---------------------*/
);// end of tooltip array

echo json_encode($tooltips);

?>