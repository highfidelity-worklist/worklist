<div id="search-head">
<div id="search-filter-wrap">
  <div>
    <form method="get" action="" id="searchForm" >
      <div class="input_box">
        <div class="searchDiv">
          <input type="text" id="query" name="query" alt="Search" size="20"
            value="<?php echo (($filter->getQuery()) ? $filter->getQuery() : ''); ?>" />
          <div class="crossSearch" id="search_reset" title="Clear Search Parameters"></div>
          <div class="commentsDiv">
            <label for="search_comments">
              <div id="search_comment" class="comment" title="Toggle include/not include comments in search"></div>
            </label>
            <input type="checkbox" name="search_comments" id="search_comments" /> 
          </div>
        </div>
      </div>
      <div class="filters">
        <div id="projectFilter">
          <label>Which project?</label>
          <?php echo $filter->getProjectSelectbox('All projects', 1) . " "; ?>
        </div>
        
        <div id="userFilter">
          <label>Users involved</label>
          <?php echo $filter->getUserSelectbox(1, 'All users') . " "; ?>
        </div>

        <?php
        $allDisplay = "All Status";
        if (isset($_SESSION['userid']) && $is_runner) {
            $status_array = array(
                "Draft",
                "Suggested", 
                "SuggestedWithBid", 
                "Bidding", 
                "Working", 
                "Functional", 
                "SvnHold", 
                "Review", 
                "Completed", 
                "Done", 
                "Pass"
            );
        } else {
            $status_array = array(
                "Suggested", 
                "SuggestedWithBid", 
                "Bidding", 
                "Working", 
                "Functional", 
                "SvnHold", 
                "Review", 
                "Completed", 
                "Done", 
                "Pass"
            );
        }

        $box = '<select id="statusCombo" name="status" multiple="multiple">';
        $box .= '<option value="ALL"' . (($filter->inStatus("ALL") || ($filter->inStatus(""))) ? ' selected="selected"' : '') .' > ' . $allDisplay . '</option>';
        
        foreach ($status_array as $status) {
            $selected = '';
            if (!isset($_GET['status'])) {
                if ($filter->inStatus($status)) {
                    $selected = ' selected="selected"';
                }
            } else {
                switch ($status) {
                    case 'Draft':
                        if ($_GET['status'] == 'draft') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Bidding':
                        if ($_GET['status'] == 'bidding') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'SuggestedWithBid':
                        if ($_GET['status'] == 'suggestedwithbid') { 
                            $selected = ' selected="selected"';
                        }
                        break;    
                    case 'Working':
                        if ($_GET['status'] == 'working') { 
                            $selected = ' selected="selected"';
                        }
                        //must be left in tact for the Jobs Underway link to produce accurate data
                        //holds true for functional and review below
                        if ($_GET['status'] == 'underway') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Functional':
                        if ($_GET['status'] == 'functional') { 
                            $selected = ' selected="selected"';
                        }
                        if ($_GET['status'] == 'underway') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'SvnHold':
                        if ($_GET['status'] == 'svnhold') { 
                            $selected = ' selected="selected"';
                        }
                        if ($_GET['status'] == 'underway') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Review':
                        if ($_GET['status'] == 'review') { 
                            $selected = ' selected="selected"';
                        }
                        if ($_GET['status'] == 'underway') { 
                            $selected = ' selected="selected"';
                        }
                        if ($_GET['status'] == 'needs-review') {
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Completed':
                        if ($_GET['status'] == 'completed') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Done':
                        if ($_GET['status'] == 'done') { 
                            $selected = ' selected="selected"';
                        }
                        if ($_GET['status'] == 'completed') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                }
            }
            $box .= '<option value="' . $status . '"' . $selected . '>' . $status . '</option>';
        }
        
        $box .= '<option value="CheckDone">Done</option>';
        $box .= '</select>';
        ?>
        <div id="jobStatusFilter">
          <label>Job status</label>
          <?php echo $box; ?>
        </div>
      </div>

    <div id="buttons">
    <?php if (isset($_SESSION['userid'])): ?>
        <p><a href="addjob.php" id="addJobButton" title="Create a new job" target="_blank">Add job</a></p>
    <?php endif; ?>
    </div>

    </form>
  </div>
  <?php 
  if (isset($_SESSION['userid'])) { 
      $statuses = array('Working', 'Functional', 'Review', 'Completed'); 
      $userstats = new UserStats($_SESSION['userid']);
      $links = 'My jobs: ';
      foreach ($statuses as $status) {
          $links .= $userstats->getJobsCount($status) > 0 
            ? '<a href="#" id="quick-links-' . strtolower($status) . '">' . $status . '</a> ' 
            : $status . ' ';
      }
  ?>
  <div id="quick-links" style="display:none">
    <?php echo $links; ?>
  </div>
  <?php } ?>
  </div>
</div>

<div style="clear: both"></div>
