<?php include dirname(__FILE__).'/config.php'; ?>
<link type="text/css" href="css/budgetHistory.css" rel="stylesheet" />

<div id="BudgetHistory">
<?php

$id = (int) $_REQUEST['id'];

if(!isset($_REQUEST['page'])) {
    echo '<iframe src="budgetHistory.php?id=' . $id . '&page=1" width="100%" height="260" frameborder="0"></iframe>';
    exit(0);
}

$page = (int) $_REQUEST['page'];
$limit = 9;
$init = ($page -1) * $limit;

// Query to get User's Budget entries
$query =  ' SELECT DATE_FORMAT(b.transfer_date, "%Y-%m-%d") AS date, b.amount, b.reason, u.nickname '
        . ' FROM ' . BUDGET_LOG . ' AS b '
        . ' INNER JOIN ' . USERS . ' AS u ON u.id = b.giver_id '
        . ' WHERE b.receiver_id = ' . $id
        . ' ORDER BY b.id DESC '
        . ' LIMIT ' . $init . ',' . $limit;

// Get total # of entries
$queryTotal =  ' SELECT COUNT(*) AS total'
        . ' FROM ' . BUDGET_LOG . ' AS b '
        . ' WHERE b.receiver_id = ' . $id;

if($result = mysql_query($queryTotal)) {
    $count = mysql_fetch_assoc($result);
    $totalPages = floor(( (int) $count['total']) / $limit);
} else {
    $total = 1;
}
if ( $count['total'] == 0){
	echo "This user hasn't been assigned any budget yet.";
	exit(0);
}

?>
<table class="budgetTable" cellspacing="0" >
  <thead>
    <tr>
      <th class="date">Date</th>
      <th class="giver">Received from</th>
      <th class="amount">Amount</th>
      <th>Reason</th>
    </tr>
  </thead>

<?php
$result = mysql_query($query);
$budgetList = array();
if ($result) {
    $i = 1;
    while ($row = mysql_fetch_assoc($result)) {
?>

    <tr class="<?php echo $i % 2 ? 'rowodd' : 'roweven'; ?>">
        <td><?php echo $row['date']; ?></td>
        <td><?php echo $row['nickname']; ?></td>
        <td><?php echo $row['amount']; ?></td>
        <td><?php echo $row['reason']; ?></td>
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
            echo '<a href="budgetHistory.php?id=' . $id . '&page=' . $i . '">' . $i . '</a> ';
        }
    }
?>
</div>