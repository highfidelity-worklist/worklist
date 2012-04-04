<!-- --looks redundant but this dialog can be used to display data related to a specific roles in future <mikewasmike 17-jun-2011>--- -->
<div id="popup-role-info" class="popup-body" title = "Role Info">
    <form name="popup-form" id="popup-form" action="" method="post">
        <input type="hidden" name="role_id" value="" />
        <table border="0" style="border:none;">
            <tr>
                <td style="width:110px">Role title</td>
                <td><span id="info-title"></span></td>
            </tr>
            <tr>
                <td>Percentage</td>
                <td><span id="info-percentage"></span>%</td>
            </tr>
            <tr>
                <td>Minimum amount</td>
                <td>$<span id="info-min-amount"></span></td>
            </tr>
        </table>
        <div class="clear"></div>
        <br/>
        <div>
            <input type="submit" id="delete_role" name="delete_role" value="Delete">
            <input type="button" name="edit" id="editRole" value="Edit">
        </div>
    </form>
</div>
