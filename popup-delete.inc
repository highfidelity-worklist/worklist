    <div id="popup-delete" class="popup-body" title = "Delete Worklist Item">
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="itemid" value="" />
                <input type="hidden" name="page" value="<?php echo $page ?>" class="popup-page-value" />

                <p class = "info-label">Are you sure you want to delete:<br />
                <span id="popup-delete-summary"></span>
                </p>
       
                <input type="submit" name="delete" value="Yes">
                <input type="submit" name="cancel" value="No">
            </form>
    </div>
