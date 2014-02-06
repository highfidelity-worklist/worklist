<?php
$sum = Fee::getSums();
?>
    <div class="hidden" id="feesDialog">
        <table>
            <tr>
                <td>
                    <b>Your fees: </b>
                </td>
            </tr>
            <tr>
                <td>this week:</td>
                <td><a href="#feesToolTip" class="feesum" id="fees-week">$<?php echo $sum['week']; ?></a></td>
            </tr>
            <tr>
                <td>this month:</td>
                <td><a href="#feesToolTip" class="feesum" id="fees-month">$<?php echo $sum['month']; ?></a></td>
            </tr>
        </table>
    </div>
