        <script type="text/javascript">
            worklistUrl = '<?php echo SERVER_URL; ?>';
        </script>
        <table id = "user-stats">
            <thead>
                <tr class = "table-hdng">
                    <th>Total/Active Jobs</th><th>Total/30 day $</th>
		    <?php if(!empty($Annual_Salary)){?> <th>Salary</th><?php }?><th>Bonus $ / %</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href = "#" id = "total-jobs"><?php echo $userStats->getTotalJobsCount(); ?></a> / <a href = "#" id = "active-jobs"><?php echo $userStats->getActiveJobsCount(); ?></a></td>
                    <td><?php
			setlocale(LC_MONETARY,'en_US');
			$_totalEarnings = $userStats->getTotalEarnings();
			echo preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$_totalEarnings));
			?> / <?php echo preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$userStats->getLatestEarnings(30))); ?></td>
		    <?php if(!empty($Annual_Salary)){?><td><?php echo preg_replace('/\.[0-9]{2,}$/','',money_format('%n',$Annual_Salary)); ?></td><?php }?>
		    <td><?php
			$_bonusPayments = $userStats->getBonusPaymentsTotal();
			echo preg_replace('/\.[0-9]{2,}$/','',money_format('%n',round($_bonusPayments))).' / '.round((($_bonusPayments + 0.000001) / ($_totalEarnings + 0.000001)) * 100).'%';
		    ?></td>
                </tr>
            </tbody>
        </table>

<!-- file with popups for user statistics-->
<?php include("dialogs/popups-userstats.inc"); ?>
