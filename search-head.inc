<div id="search-head" <?php echo isset($_SESSION['userid']) ? 'style="height:100px"' : 'style="height:50px"'; ?>>
  <div style="width:340px;">
<?php if (isset($_SESSION['userid'])) { ?>
<div id="buttons" style="padding-top:10px;">
<p><input type="submit" id="add" name="add" value="Add" class="iToolTip addButton" /> 
</p>
</div>
<div id="status-wrap" style="width:340px;">
        <form action="" id="status-update-form" style="width:340px;"><?php echo $nick?> is <span id="status-lbl"><b><?php echo $current_status?></b></span>
                <input style="display: none;" type="text" maxlength="45" id="status-update" name="status-update"
                        value="<?php echo $current_status?>"></input>
                <div id="status-share" style="display: none; float:right; width:122px;">
                        <input type="submit" value="Share" id="status-share-btn"></input>
                </div>
        </form>
    </div>
        <?php } ?>
        <div style="clear:both"></div>
  </div>
<div id="search-filter-wrap"<?php echo (isset($_SESSION['userid'])&& $is_runner) ? 'style="right:220px"' : 'style="right:20px"'; ?>>
    <div >
        <form method="get" action="" id="searchForm" >
            <?php echo $filter->getUserSelectbox(1); ?>
                <?php echo $filter->getStatusSelectbox(); ?>
                <div class="input_box">
                    <input type="text" id="query" value="<?php echo (($filter->getQuery()) ? $filter->getQuery() : ''); ?>" name="query" alt="Search" size="20" />
                    <a id="search" href="" class="searchIcon"><img height="23" width="24" border="0" alt="zoom" src="images/spacer.gif"></a>
                <a id="search_reset" href="" class="searchIcon"><img src="images/cross.png"></a>            </div>
        </form>
    </div>
    <?php if(isset($_SESSION['userid'])){ 
        $statuses = array('WORKING', 'REVIEW', 'COMPLETED'); 
        $userstats = new UserStats($_SESSION['userid']);
        $links = 'My jobs: ';
        foreach($statuses as $status){
            $links .= $userstats->getJobsCount($status) > 0 ? '<a href="#" id="quick-links-' . strtolower($status) . '">' . $status .'</a> ' : $status . ' ';
        }
    ?>
        <div id="quick-links">
            <?php echo $links; ?>
        </div>
    <?php } ?>
  </div>
<?php if(isset($_SESSION['userid']) && $is_runner){ ?>
<div id="be-block" >
    <table id="be-table">
        <tr>
            <td class="be-table_cell1 iToolTip budgetRemaining"><strong>Remaining Funds:</strong></td>
            <td class="be-table_cell2 iToolTip budgetRemaining"><strong>
            <?php echo(money_format('$ %i', $user->getRemainingFunds())); ?></strong></td>
        </tr>
        <tr>
            <td onclick="budgetExpand(0)" class="be-table_cell1 iToolTip budgetAllocated">Allocated:</td>
            <td onclick="budgetExpand(0)" class="be-table_cell2 iToolTip budgetAllocated">
            <?php echo(money_format('$ %i', $user->getAllocated())); ?></td>
        </tr>
        <tr>
            <td onclick="budgetExpand(1)" class="be-table_cell1 iToolTip budgetSubmitted">Submitted:</td>
            <td onclick="budgetExpand(1)" class="be-table_cell2 iToolTip budgetSubmitted">
            <?php echo(money_format('$ %i', $user->getSubmitted())); ?></td>
        </tr>
        <tr>
            <td onclick="budgetExpand(2)" class="be-table_cell1 iToolTip budgetPaid">Paid:</td>
            <td onclick="budgetExpand(2)" class="be-table_cell2 iToolTip budgetPaid">
            <?php echo(money_format('$ %i', $user->getPaid())); ?></td>
        </tr>
    </table>
</div>

<?php } ?>
</div>

<div style="clear: both"></div>