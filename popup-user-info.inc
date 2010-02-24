    <!-- Popup for user info-->
    <div id="popup-user-info" class="popup-body" title = "User Info">
            <p class = "info-label">Nickname<br />
            <span id="info-nickname"></span>
            </p>

            <p class = "info-label">Email<br />
            <span id="info-email"></span>
            </p>

            <p class = "info-label">About<br />
            <span id="info-about"></span>
            </p>

            <p class = "info-label">Preferred Method of Contact<br />
            <span id="info-contactway"></span>
            </p>

            <p class = "info-label">Preferred Method of Payment<br />
            <span id="info-payway"></span>
            </p>

            <p class = "info-label">Strongest Skills<br />
            <span id="info-skills"></span>
            </p>

            <p class = "info-label">Time zone<br />
            <span id="info-timezone"></span>
            </p>

            <p class = "info-label">Joined<br />
            <span id="info-joined"></span>
            </p>
            
	        <?php if (!empty($_SESSION['is_runner'])) { ?>
            <p class="info-label">W9 Approved<br />
            <input type="checkbox" id="approve" />
            </p>
            <?php } ?>
    
            <form id = "roles" method="post" >
            <p class = "info-label">Roles<br />
            <input type="checkbox" name="isrunner" value="isrunner" id="info-isrunner" /><span>Runner</span>
            <input type="checkbox" name="ispayer" value="ispayer" id="info-ispayer" /><span>Payer</span>
            </p>
            <input type="hidden" name="userid" id="userid" value="">
            <input type="hidden" name="letter" id="hid_letter" value="">
            <input type="hidden" name="page" id="hid_page" value="">
	        <?php if (!empty($_SESSION['is_runner'])) { ?>
            <input type="submit" name="save_roles" value="Save Roles">
            <?php } ?>
	        </form>

            <form id = "popup-form" action="settings.php" method="post">

            </form>
    </div><!-- end of popup-bid-info -->
