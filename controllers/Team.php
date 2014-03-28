<?php

class TeamController extends Controller {
    public function run() {
        $this->write('cur_letter', isset( $_POST['letter'] ) ? $_POST['letter'] : "all");
        $this->write('cur_page', isset( $_POST['page'] ) ? intval($_POST['page'] ) : 1);
        $this->write('showUser', isset($_REQUEST['showUser']) ? $_REQUEST['showUser'] : 0);
        $this->write('showUserTab', isset($_REQUEST['tab']) ? $_REQUEST['tab'] : '');
        $this->write('newStats', UserStats::getNewUserStats());
    }
}
