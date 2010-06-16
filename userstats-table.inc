        <script type="text/javascript">
            worklistUrl = '<?php echo SERVER_URL; ?>';
        </script>
        <table id = "user-stats">
            <thead>
                <tr class = "table-hdng">
                    <th>Total Jobs</th><th>Active Jobs</th><th>Total $</th><th>Fees (30 days)</th>
					<?php if(!empty($Annual_Salary)){?> <th>Salary</th><?php }?><th>Love</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href = "#" id = "total-jobs"><?php echo $userStats->getTotalJobsCount(); ?></a></td>
                    <td><a href = "#" id = "active-jobs"><?php echo $userStats->getActiveJobsCount(); ?></a></td>
                    <td>$<?php echo $userStats->getTotalEarnings(); ?></td>
                    <td><a href = "#" id = "latest-earnings">$<?php echo $userStats->getLatestEarnings(30); ?></a></td>
					<?php if(!empty($Annual_Salary)){?><td>$<?php echo $Annual_Salary; ?></td><?php }?>
                    <td><a href = "#" id = "love"><?php echo $userStats->getLoveCount(); ?>(<?php echo $userStats->getUniqueLoveCount(); ?>)</a></td>
                </tr>
            </tbody>
        </table>

<!-- file with popups for user statistics-->
<?php include("popups-userstats.inc"); ?>