<?php

$tooltips = array(				  
/************************
*	Worklist tooltips   *
************************/
					  
	/* List page top tooltips 		*/
	
	'menuWorklist'	=> 'Explore jobs or add new ones.',
	'menuJournal'	=> 'Chat',
	'menuLove'		=> 'Show appreciation for cool things people have done',
	'menuRewarder'	=> 'Give earned points/money to other teammates.',
	'menuReports'	=> 'Audit all the work and money flow.',
	'jobsBidding'	=> 'See more stats on jobs and team members.',
	
	
	/* List's list tooltips 		*/
	'addButton'		=> 'Create a new job that needs to get done.',
	'hoverJobRow'	=> 'View, edit, or make a bid on this job.',
	

	/* Item tooltips 				*/ 
	
        'cR'            => 'Start a code review on this job.',
        'endCr'         => 'End a code review on this job.',
        'cRDisallowed'  => 'You are not authorized to Code Review this job.',
        'cRSetToFunctional'=> 'Set this job to Functional then to Review to enable Code Review.',
        'endCrDisallowed' => 'You are not authorized to End Code Review on this job',
        'addFee'	   => 'Add a fee you would like to be paid for work done on this job.',
        'addBid'        => 'Make an offer to do this job.',
        'changeSBurl'   => 'Click to change your sandbox url.',
	
	/* Budget Info tooltips         */

    'budgetRemaining1' => 'Funds still available to use towards jobs for all my budgets',
    'budgetAllocated1' => 'Funds linked to fees in active jobs (Working, Functional, Review, Completed) for all my budgets',
    'budgetSubmitted1' => 'Funds linked to fees in Done\'d jobs that are not yet paid for all my budgets',
    'budgetPaid1'      => 'Funds linked to fees that have been Paid through system for all my budgets',
    'budgetTransfered1'  => 'Funds granted to others via giving budget for all my budgets',
    'budgetManaged1'  => 'Total amount of budget funds granted to me since joining Worklist',
    
    /* Update Budget Info tooltips         */
    'budgetSave' => 'Save changes made to title or notes',
    'budgetClose' => 'Reconcile &amp; close this open budget',
    'budgetRemaining2' => 'Funds still available to use towards jobs',
    'budgetAllocated2' => 'Funds linked to fees in active jobs (Working, Functional, Review, Completed)',
    'budgetSubmitted2' => 'Funds linked to fees in Done\'d jobs that are not yet paid',
    'budgetPaid2'      => 'Funds linked to fees that have been Paid through system',
    'budgetTransfered2'  => 'Funds granted to others via giving budget',
	
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