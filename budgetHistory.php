<?php 
include dirname(__FILE__).'/config.php'; 
require_once 'class.session_handler.php';
include("functions.php");
?>
<link type="text/css" href="css/budgetHistory.css" rel="stylesheet" />

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
        . ' b.reason, b.active, b.notes, b.giver_id, b.id AS budget_id, u.nickname '
        . ' FROM ' . BUDGETS . ' AS b '
        . ' INNER JOIN ' . USERS . ' AS u ON u.id = b.giver_id '
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
      <th class="date">Date</th>
      <th class="giver">Grantor</th>
      <th class="amount">Amount</th>
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
            $_SESSION['is_payer'] ||
            $row['giver_id'] == $userId) {
            if (!empty($row['notes'])) {
                $notes = " title='Notes: " . $row['notes'];
            } else {
                $notes = " title='Notes: None";
            }
            $notes .=  "' ";
            $classBudgetRow = " budgetRow";
        } else {
            $classBudgetRow = "";
        }
            
?>

    <tr class="<?php echo ($i % 2 ? 'rowodd' : 'roweven') . $classBudgetRow; ?>"  data-budgetid="<?php echo $row['budget_id']; ?>"
        <?php echo (!empty($notes)) ? $notes : ""; ?>
    >
        <td><?php echo $row['date']; ?></td>
        <td><?php echo $row['nickname']; ?></td>
        <td><?php echo $row['amount']; ?></td>
        <td><?php echo $row['reason']; ?></td>
        <td><?php echo ($row['active'] == 1) ? "Yes" : "No"; ?></td>
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