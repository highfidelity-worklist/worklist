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
	
	'cR'	        => 'Start a code review on this job.',
    'cRDisallowed'	=> 'You are not authorized to Code Review this job.',
	'addFee'	    => 'Add a fee you would like to be paid for work done on this job.',
	'addBid'	    => 'Make an offer to do this job.',
	
	/* Budget Info tooltips         */

    'budgetRemaining' => 'Total funds I have ever been assigned, less all of the below.',
    'budgetAllocated' => 'Money total from Jobs I run that are in Working, Review & Completed Statuses.',
    'budgetSubmitted' => 'Money total From Jobs I set to Done, but have not been paid.',
    'budgetPaid'      => 'Money Paid out on Done jobs.',
	
	/* Add Job tooltips 			*/
    'enterAmount'     =>  'Enter the amount you want to be paid if this job is accepted and done.',
    'enterNotes'      =>  'Enter detailed code review info in Comments Section.',
    'enterCrAmount'	  =>  'Recommended review fee based on project settings.',

    /* Add Project tooltips 			*/
    'addProj' => 'Add a new project to the Worklist.',
	

/*---------------------*/
);// end of tooltip array

echo json_encode($tooltips);

?>