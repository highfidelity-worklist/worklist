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
        echo json_encode($userStats->getUserItems('DONE', $page));
        break;

    case 'activeJobs':
        echo json_encode($userStats->getUserItems('WORKING', $page));
        break;

    case 'reviewJobs':
        echo json_encode($userStats->getUserItems('REVIEW', $page));
        break;

    case 'completedJobs':
        echo json_encode($userStats->getUserItems('COMPLETED', $page));
        break;

    case 'latest_earnings':
        echo json_encode($userStats->getLatestEarningsJobs(30, $page));
        break;

    case 'counts':
			setlocale(LC_MONETARY,'en_US');
			$_totalEarnings = $userStats->getTotalEarnings();
			$_bonusPayments = $userStats->getBonusPaymentsTotal();
			$ajaxTotalEarnings=  preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$_totalEarnings));
		    $ajaxLatestEarnings= preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$userStats->getLatestEarnings(30)));
			$bonus= preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$_bonusPayments));
			$bonusPercent=round((($_bonusPayments + 0.000001) / ($_totalEarnings + 0.000001)) * 100).'%';

        echo json_encode(array(
                            'total_jobs' => $userStats->getTotalJobsCount(),
                            'active_jobs' => $userStats->getActiveJobsCount(),
                            'total_earnings' => $ajaxTotalEarnings,
                            'latest_earnings' => $ajaxLatestEarnings,
							'bonus_total' => $bonus,
							'bonus_percent' => $bonusPercent
                              ));
        break;
    }
