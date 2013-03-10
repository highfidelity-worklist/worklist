<?php
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("config.php");
include("class.session_handler.php");
include("functions.php");

if (isset($_REQUEST['id'])) {
    $userId = (int)$_REQUEST['id'];
} else {
    die("No id provided");
}

    $userStats = new UserStats($userId);
    $userStats->setItemsPerPage(9);

    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;

    switch($_REQUEST['statstype']){

    case 'love':
        echo json_encode($userStats->getTotalLove($page));
        break;

    case 'doneJobs':
        echo json_encode($userStats->getUserItems('Done', $page));
        break;

    case 'activeJobs':
        echo json_encode($userStats->getActiveUserItems(9, $page));
        break;

    case 'reviewJobs':
        echo json_encode($userStats->getUserItems('Review', $page));
        break;
        
    case 'workingJobs':
        echo json_encode($userStats->getUserItems('Working', $page));
        break;

    case 'completedJobsWithStats':
        echo json_encode($userStats->getCompletedJobsWithStats());
        break;

    case 'completedJobs':
        echo json_encode($userStats->getUserItems('Completed', $page));
        break;

    case 'latest_earnings':
        echo json_encode($userStats->getLatestEarningsJobs(30, $page));
        break;
    case 'following':
        echo json_encode($userStats->getFollowingJobs($page));
        break;
    
    case 'runnerTotalJobs':
        echo json_encode($userStats->getTotalRunnerItems($page));        
        break;

    case 'runnerActiveJobs':
        echo json_encode($userStats->getActiveRunnerItems($page));
        break;

    case 'project_history':
        $projectId = $_REQUEST['project_id'];
        echo json_encode($userStats->getUserItemsForASpecificProject('Done', $projectId));
        break;

    case 'counts':
			setlocale(LC_MONETARY,'en_US');
			$_totalEarnings = $userStats->getTotalEarnings();
			$_bonusPayments = $userStats->getBonusPaymentsTotal();
			$ajaxTotalEarnings =  preg_replace('/\.[0-9]{2,}$/','',money_format('%n',round($_totalEarnings)));
		    $ajaxLatestEarnings = preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$userStats->getLatestEarnings(30)));
			$bonus = preg_replace('/\.[0-9]{2,}$/','',money_format('%n',round($_bonusPayments)));
			$_bonusPercent = round((($_bonusPayments + 0.00000001) / ($_totalEarnings + 0.000001)) * 100,2);

        echo json_encode(array(
                            'total_jobs' => $userStats->getTotalJobsCount(),
                            'active_jobs' => $userStats->getActiveJobsCount(),
                            'total_earnings' => $ajaxTotalEarnings,
                            'latest_earnings' => $ajaxLatestEarnings,
							'bonus_total' => $bonus,
							'bonus_percent' => $_bonusPercent.'%'
                              ));
        break;
    }
