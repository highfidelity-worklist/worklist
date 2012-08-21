<?php
//  Copyright (c) 2009-2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com

include("class/Report.class.php");
$report = new Report();

$customer = $report->getCustomerInfo(intval($_GET['cid']));
$customer = $customer[0];
#var_dump($customer);
?>
<table border="0" cellspacing="5" cellpadding="5">

    <tr><td>Companyname</td><td><?php echo $customer->company_name ?></td></tr>
    <tr><td>Address</td><td><?php echo $customer->address_street ?></td></tr>
    <tr><td>&nbsp;</td><td><?php echo $customer->address_city.', '.$customer->address_state.' '.$customer->address_zip; ?></td></tr>
    <tr><td>Contact E-mail</td><td><a href="mailto:<?php echo $customer->contact_email ?>"><?php echo $customer->contact_email ?></a></td></tr>
    <tr><td>Contact Name</td><td><?php echo $customer->contact_first_name.' '.$customer->contact_last_name ?></td></tr>
    <tr><td>Contact Phone</td><td><?php echo $customer->contact_phone ?></td></tr>
</table>
