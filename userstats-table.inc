        <script type="text/javascript">
            worklistUrl = '<?php echo SERVER_URL; ?>';
        </script>
        <table id = "user-stats">
            <thead>
                <tr class = "table-hdng">
<?php if ($user->isRunner() && ! empty($hasRunJobs)) : ?>
                    <th>Bids Accepted Avg</th>
                    <th>Avg TimeToPay</th>
<?php endif; ?>
                    <th>Total/Active Jobs</th>
                    <th>Total/30 day $</th>
        
            <?php if(!empty($Annual_Salary)){?> <th>Salary</th><?php }?><th>Bonus $ / %</th>
<?php if ($hasBeenMechanic) : ?>
                    <th>% On Time</th>
<?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
<?php if ($user->isRunner() && ! empty($hasRunJobs)) : ?>
                    <td><?php echo relativeTime($userStats->getTimeBidAcceptedAvg(), false, false, false); ?></td>
                    <td><?php echo relativeTime($userStats->getTimeToPayAvg(), false, false, false); ?></td>
<?php endif; ?>
                    <td>
                        <?php if ($userStats->getTotalJobsCount() > 0) {?>
                            <a href = "#" id = "total-jobs"><?php echo $userStats->getTotalJobsCount(); ?></a>
                        <?php } else echo "0";?>
                            / 
                        <?php if ($userStats->getActiveJobsCount() > 0) {?>
                            <a href = "#" id = "active-jobs"><?php echo $userStats->getActiveJobsCount(); ?></a>
                        <?php } else echo "0";?>
                    </td>
                    <td><?php
            setlocale(LC_MONETARY,'en_US');
            $_totalEarnings = $userStats->getTotalEarnings();
            echo preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$_totalEarnings));
            ?> / <?php echo preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$userStats->getLatestEarnings(30))); ?></td>
            <?php if(!empty($Annual_Salary)){?><td><?php echo preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$Annual_Salary)); ?></td><?php }?>
            <td><?php
            $_bonusPayments = $userStats->getBonusPaymentsTotal();
            $_bonusPercent = round((($_bonusPayments + 0.00000001) / ($_totalEarnings + 0.000001)) * 100,2);
            
            echo preg_replace('/\.[0-9]{2,}$/','',money_format('%n',round($_bonusPayments))).' / '.$_bonusPercent.'%';
            ?></td>
<?php if ($hasBeenMechanic) : ?>
                    <td><?php echo $userStats->getDoneOnTimePercentage() . "%"; ?></td>
<?php endif; ?>
                </tr>
            </tbody>
        </table>

<!-- file with popups for user statistics-->
<?php include("dialogs/popups-userstats.inc"); ?>
