<?php if (isset($_SESSION['userid'])) { ?>
<div id="buttons" style="padding-top:10px;">
<p><input type="submit" id="add" name="add" value="Add Job" class="iTooltip addButton" /></p>
</div>
<?php } ?>

<div id="search-head" <?php echo isset($_SESSION['userid']) ? 'style="height:50px;"' : 'style="height:50px;"'; ?>>
  <div style="width:340px;">
<?php if (isset($_SESSION['userid'])) { ?>
    <?php } ?>
        <div style="clear:both"></div>
  </div>
<div id="search-filter-wrap"<?php echo (isset($_SESSION['userid'])&& $is_runner) ? 'style="right:-6px"' : 'style="right:-6px"'; ?>>
    <div >
        <form method="get" action="" id="searchForm" >
<?php 
                echo $filter->getProjectSelectbox('All Projects', 1) . " ";
                echo $filter->getUserSelectbox(1, 'All Users') . " ";
                $allDisplay = "All Status";
                if(isset($_SESSION['userid']) && $is_runner){
                $status_array = array("DRAFT","SUGGESTED", "SUGGESTEDwithBID", "BIDDING", "WORKING", "FUNCTIONAL", "REVIEW", "COMPLETED", "DONE", "PASS");
                } else {
                $status_array = array("SUGGESTED", "SUGGESTEDwithBID", "BIDDING", "WORKING", "FUNCTIONAL", "REVIEW", "COMPLETED", "DONE", "PASS");
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
            
                <div class="input_box">
                    <input type="text" id="query" value="<?php echo (($filter->getQuery()) ? $filter->getQuery() : ''); ?>" name="query" alt="Search" size="20" />
                    <a id="search" href="" class="searchIcon"><img height="23" width="24" border="0" alt="zoom" src="images/spacer.gif"></a>
                <?php 
                    if ($filter->getInComment() == 1) {
                ?>
                    <a id="search_comment" href="" class="searchIcon inComment" title="Include Comments in search"><img src="images/comment.jpg"/></a>
                <?php } else { ?>
                    <a id="search_comment" href="" class="searchIcon" title="Do not include comments in search"><img src="images/comment_no.jpg"/></a>
                <?php } ?>
                <a id="search_reset" href="" class="searchIcon" title="Clear Search Parameters"><img src="images/cross.png"/></a>
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
