<?php 
include dirname(__FILE__).'/config.php'; 
require_once 'class.session_handler.php';
include("functions.php");
?>
<div id="BudgetHistory">
<?php

$id = (int) $_REQUEST['id'];

$fromUserid = "n";
if(isset($_REQUEST['fromUserid'])) {
    $fromUserid = $_REQUEST['fromUserid'];
}

if(!isset($_REQUEST['page'])) {
    $_REQUEST['page'] = 1;
}

$userId = getSessionUserId();
$user = new User();
if ($userId > 0) {
    $user->findUserById($userId);
} else {
    $user->setId(0);
}
$inDiv = $_REQUEST['inDiv'];
$page = (int) $_REQUEST['page'];
$limit = 8;
$init = ($page -1) * $limit;
$fromUseridFilter = "";
if ($fromUserid == "y") {
    $fromUseridFilter = " AND b.giver_id = " . $userId;
}

// Query to get User's Budget entries
$query =  ' SELECT DATE_FORMAT(b.transfer_date, "%Y-%m-%d") AS date, b.amount,'
        . ' IF( b.active=1, b.remaining, 0.00) AS remaining, b.reason, b.active, b.notes, b.seed, b.id AS budget_id, '
        . '(SELECT COUNT(s.giver_id) FROM ' . BUDGET_SOURCE . 
            ' AS s WHERE s.budget_id = b.id AND s.giver_id = ' . $userId . ') AS userid_count, '
        . '(SELECT COUNT(DISTINCT giver_id) FROM ' . BUDGET_SOURCE . ' AS s WHERE s.budget_id = b.id ) AS givers_count, '
        . '(SELECT u.nickname FROM ' . USERS . ' AS u, ' . BUDGET_SOURCE 
        . ' AS s WHERE u.id = s.giver_id AND s.budget_id = b.id LIMIT 0, 1) AS nickname '
        . ' FROM ' . BUDGETS . ' AS b '
        . ' WHERE b.receiver_id = ' . $id
        . $fromUseridFilter
        . ' ORDER BY b.id DESC '
        . ' LIMIT ' . $init . ',' . $limit;

// Get total # of entries
$queryTotal =  ' SELECT COUNT(*) AS total'
        . ' FROM ' . BUDGETS . ' AS b '
        . ' WHERE b.receiver_id = ' . $id
        . $fromUseridFilter;

if($result = mysql_query($queryTotal)) {
    $count = mysql_fetch_assoc($result);
    $totalPages = floor(( (int) $count['total'] - 1) / $limit) + 1;
} else {
    $total = 1;
}
if ( $count['total'] == 0){
	echo "This user hasn't been assigned any budget yet.";
	exit(0);
}
if ($page == 1) {
    echo "<div class='budgetHistoryContent'>";
}
?>
<table class="budgetTable" cellspacing="0" >
  <thead>
    <tr>
      <th class="date">Created</th>
      <th class="id">ID #</th>
      <th class="giver">Grantor</th>
      <th class="amount">Amount</th>
      <?php if (!empty($id) && $userId == $id) { ?>
      <th class="amount">Remaining</th>
      <?php } ?>
      <th class="for">For</th>
      <th class="active">Active</th>
    </tr>
  </thead>

<?php
$result = mysql_query($query);
$budgetList = array();
if ($result) {
    $i = 1;
    while ($row = mysql_fetch_assoc($result)) {
        $notes = "";
        if ($userId == $id ||
            (array_key_exists('is_payer', $_SESSION)  && $_SESSION['is_payer']) ||
            $row['userid_count'] > 0) {
            if (!empty($row['notes'])) {
                $notes = " title='" . $row['budget_id'] . " - Notes: " . $row['notes'];
            } else {
                $notes = " title='" . $row['budget_id'] . " - Notes: None";
            }
            $notes .=  "' ";
            $classBudgetRow = " budgetRow";
        } else {
            $classBudgetRow = "";
        }
            
?>

    <tr class="<?php echo ($i % 2 ? 'rowodd' : 'roweven') . $classBudgetRow; ?>"  data-budgetid="<?php echo $row['budget_id']; ?>"
        <?php echo (!empty($notes)) ? $notes : $row['budget_id'] ; ?>
    >
        <td><?php echo $row['date']; ?></td>
        <td><?php echo $row['budget_id']; ?></td>
        <td><?php echo ($row['givers_count'] == 1 ) ? $row['nickname'] : "Various"; ?></td>
        <td><?php echo $row['amount']; ?></td>
        <?php if (!empty($id) && $userId == $id) { ?>
        <td <?php if ($row['remaining'] < 0) echo 'class="red"'; ?>>
            <?php echo $row['remaining']; ?></td>
        <?php } ?>
        <td><?php echo $row['reason']; ?></td>
        <td><?php echo ($row['active'] == 1) ? "open" : "closed"; ?></td>
    </tr>

<?php
    $i++;
    }
} 
?>

</table>
<div class="pages">
<?php 
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            echo $i . ' ';
        } else {
            echo '<a href="javascript:Budget.budgetHistory({inDiv: \'' . $inDiv . '\', id: ' . $id . ', page: ' . $i . ', fromUserid: \'' . $fromUserid . '\'});">' . $i . '</a> ';
        }
    }
?>
</div>
<?php 
if ($page == 1) {
    echo "</div>";
}
?>
