        <script type="text/javascript">
            worklistUrl = '<?php echo SERVER_URL; ?>';
        </script>
        <table id = "user-stats">
            <thead>
                <tr class = "table-hdng">
                    <th>Total Jobs</th><th>Active Jobs</th><th>Total $</th><th>Fees (30 days)</th><th>Love</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href = "#" id = "total-jobs"><?php echo $userStats->getTotalJobsCount(); ?></a></td>
                    <td><a href = "#" id = "active-jobs"><?php echo $userStats->getActiveJobsCount(); ?></a></td>
                    <td>$<?php echo $userStats->getTotalEarnings(); ?></td>
                    <td>$<?php echo $userStats->getLatestEarnings(30); ?></td>
                    <td><a href = "#" id = "love"><?php echo $userStats->getLoveCount(); ?></a></td>
                </tr>
            </tbody>
        </table>

        <div id = "done-jobs-popup" style = "display: none;" title = "Done jobs"> 
            <table>
                <thead>
                    <tr class = "table-hdng">
                        <th>Job #</th><th>Summary</th><th>Creator</th><th>Runner</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <div id = "active-jobs-popup" style = "display: none;" title = "Active jobs"> 
            <table>
                <thead>
                    <tr class = "table-hdng">
                        <th>Job #</th><th>Summary</th><th>Creator</th><th>Runner</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

        <div id = "lovelist-popup" style = "display: none;"  title = "Love received"> 
            <table>
                <thead>
                    <tr class = "table-hdng">
                        <th>From</th><th>Why</th><th>When</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>