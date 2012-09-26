
<div id="search-head" style="height:50px;">
<div id="search-filter-wrap" style="right:-6px">
    <div >
        <form method="get" action="" id="searchForm" >
                <div class="input_box">
                  <div class="searchDiv">
                    <input type="text" id="query" value="<?php echo (($filter->getQuery()) ? $filter->getQuery() : ''); ?>" name="query" alt="Search" size="20" />
                    <div id="search" class="search"> &nbsp;</div>
                  </div>
                <?php 
                    if ($filter->getInComment() == 1) {
                ?>
                    <div id="search_comment" class="inComment comment" title="Include Comments in search"></div>
                <?php } else { ?>
                    <div id="search_comment" title="Do not include comments in search" class="comment_no"></div>
                <?php } ?>
                <div class="crossSearch" id="search_reset" title="Clear Search Parameters"></div>
                </div>

<?php if (isset($_SESSION['userid'])) { ?>
    <div id="buttons">
        <p><input type="submit" id="addJob" name="addJob" value="Add Job" class="iTooltip addButton" /></p>
    </div>
<?php } ?>
    <div class="filters">
<?php 
                echo $filter->getProjectSelectbox('All Projects', 1) . " ";
                echo $filter->getUserSelectbox(1, 'All Users') . " ";
                $allDisplay = "All Status";
                if(isset($_SESSION['userid']) && $is_runner){
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

                $box = '<select id="statusCombo" name="status" multiple="multiple" style="display:none">';
                $box .= '<option value="ALL"' . (($filter->inStatus("ALL") ) ? ' selected="selected"' : '') . '> ' . $allDisplay . '</option>';
                
                foreach ($status_array as $status) {
                    $selected = '';
                    if(!isset($_GET['status'])) {
                        if ($filter->inStatus($status)) {
                            $selected = ' selected="selected"';
                        }
                    } else {
                        switch ($status) {
                            case 'DRAFT':
                                if($_GET['status'] == 'draft') { 
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'BIDDING':
                                if($_GET['status'] == 'bidding') { 
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'SUGGESTEDwithBID':
                                if($_GET['status'] == 'suggestedwithbid') { 
                                    $selected = ' selected="selected"';
                                }
                                break;    
                            case 'WORKING':
                                if($_GET['status'] == 'working') { 
                                    $selected = ' selected="selected"';
                                }
                                //must be left in tact for the Jobs Underway link to produce accurate data
                                //holds true for functional and review below
                                if($_GET['status'] == 'underway') { 
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'FUNCTIONAL':
                                if($_GET['status'] == 'functional') { 
                                    $selected = ' selected="selected"';
                                }
                                if($_GET['status'] == 'underway') { 
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'SVNHold':
                                if($_GET['status'] == 'svnhold') { 
                                    $selected = ' selected="selected"';
                                }
                                if($_GET['status'] == 'underway') { 
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'REVIEW':
                                if($_GET['status'] == 'review') { 
                                    $selected = ' selected="selected"';
                                }
                                if($_GET['status'] == 'underway') { 
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'COMPLETED':
                                if($_GET['status'] == 'completed') { 
                                    $selected = ' selected="selected"';
                                }
                                break;
                            case 'DONE':
                                if($_GET['status'] == 'done') { 
                                    $selected = ' selected="selected"';
                                }
                                if($_GET['status'] == 'completed') { 
                                    $selected = ' selected="selected"';
                                }
                                break;
                        }
                    }
                    $box .= '<option value="' . $status . '"' . $selected . '>' . $status . '</option>';
                }
                
                $box .= '<option value="CheckDone">Done</option>';
                $box .= '</select>';
                echo $box;
            ?>
            </div>
        </form>
    </div>
    <?php if(isset($_SESSION['userid'])){ 
        $statuses = array('WORKING', 'FUNCTIONAL', 'REVIEW', 'COMPLETED'); 
        $userstats = new UserStats($_SESSION['userid']);
        $links = 'My jobs: ';
        foreach($statuses as $status){
            $links .= $userstats->getJobsCount($status) > 0 ? '<a href="#" id="quick-links-' . strtolower($status) . '">' . $status .'</a> ' : $status . ' ';
        }
    ?>
        <div id="quick-links" style="display:none">
            <?php echo $links; ?>
        </div>
    <?php } ?>
  </div>

<?php require_once('dialogs/popup-budget.inc'); ?>
</div>

<div style="clear: both"></div>
