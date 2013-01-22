<div id="invite-people" class="popup-body" title="Invite User to this Job">
    <?php if ($user_id > 0):?>
    <form action="" method="post" name="invite-form" onSubmit="return sendInviteForm();">
        <div>
            <label for="invite">Invite</label>
            <input id="invite" type="text" class="invite ac_input" name="invite" autocomplete="on" />
        </div>
        <script type="text/javascript">
        $(document).ready(function() {
            $('.invite').autocomplete('getusers.php', {
                multiple: false,
                extraParams: { nnonly: 1 }
            });
        });
        </script>
        <div class="clear">
            <input type="submit" name="invite-people" value="Invite" class="bottonContainer" />
        </div>
    </form>
    <?php endif;?>
</div>
