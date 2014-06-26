<?php

class ProjectController extends Controller {
    public function run($action, $param = '') {
        $method = '';
        switch($action) {
            case 'view':
            case 'addCodeReviewer':
                $method = $action;
                break;
            default:
                $method = 'view';
                $param = $action;
        }
        $params = preg_split('/\//', $param);
        call_user_func_array(array($this, $method), $params);
    }

    public function view($id) {
        try {
            $project = Project::find($id);
        } catch(Exception $e) {
            $error  = $e->getMessage();
            die($error);
        }

        $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
        $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;

        //get the project owner
        $project_user = new User();
        $project_user->findUserById($project->getOwnerId());
        $this->write('project_user', $project_user);

        $userId = getSessionUserId();
        if ($userId > 0) {
            initUserById($userId);
            $user = new User();
            $user->findUserById($userId);
            // @TODO: this is overwritten below..  -- lithium
            $nick = $user->getNickname();
            $userbudget =$user->getBudget();
            $budget = number_format($userbudget);
            $is_owner = $project->isOwner($user->getId());
            $is_admin = $user->getIs_admin();
        } else {
            $is_owner = false;
            $is_admin = false;
        }

        $runners = $project->getRunners();

        if (isset($_REQUEST['save_project']) && ( $is_runner || $is_payer || $is_owner)) {
            $project
                ->setDescription($_REQUEST['description'])
                ->setShortDescription($_REQUEST['short_description']);

            $project->setWebsite($_REQUEST['website']);
            $project->setTestFlightEnabled(isset($_REQUEST['testflight_enabled']) ? 1 : 0);
            $project->setTestFlightTeamToken($_REQUEST['testflight_team_token']);
            $cr_anyone = ($_REQUEST['cr_anyone']) ? 1 : 0;
            $cr_3_favorites = ($_REQUEST['cr_3_favorites']) ? 1 : 0;
            $cr_project_admin = isset($_REQUEST['cr_project_admin']) ? 1 : 0;
            $cr_users_specified = isset($_REQUEST['cr_users_specified']) ? 1 : 0;
            $cr_job_runner = isset($_REQUEST['cr_job_runner']) ? 1 : 0;
            $internal = isset($_REQUEST['internal']) ? 1 : 0;
            $hipchat_enabled = isset($_REQUEST['hipchat_enabled']) ? 1 : 0;
            $project->setCrAnyone($cr_anyone);
            $project->setCrFav($cr_3_favorites);
            $project->setCrAdmin($cr_project_admin);
            $project->setCrRunner($cr_job_runner);
            $project->setCrUsersSpecified($cr_users_specified);
            $project->setHipchatEnabled($hipchat_enabled);
            $project->setHipchatNotificationToken($_REQUEST['hipchat_notification_token']);
            $project->setHipchatRoom($_REQUEST['hipchat_room']);
            $project->setHipchatColor($_REQUEST['hipchat_color']);
            
            if ($user->getIs_admin()) {
                $project->setInternal($internal);
            }
            
            if ($_REQUEST['logoProject'] != "") {
                $project->setLogo($_REQUEST['logoProject']);
            }
            if (isset($_REQUEST['noLogo']) && $_REQUEST['noLogo'] == "1") {
                $project->setLogo("");
            }
            $project->save();
            // we clear post to prevent the page from redirecting
            $_POST = array();
        }

        $project_id = $project->getProjectId();
        $hide_project_column = true;

        // save,edit,delete roles <mikewasmie 16-jun-2011>
        if ($is_runner || $is_payer || $project->isOwner($userId)) {
            if ( isset($_POST['save_role'])) {
                $args = array('role_title', 'percentage', 'min_amount');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_POST[$arg]);
                }
                $role_id = $project->addRole($project_id,$role_title,$percentage,$min_amount);
            }

            if (isset($_POST['edit_role'])) {
                $args = array('role_id','role_title_edit', 'percentage_edit', 'min_amount_edit');
                foreach ($args as $arg) {
                    $$arg = mysql_real_escape_string($_POST[$arg]);
                }
                $res = $project->editRole($role_id, $role_title_edit, $percentage_edit, $min_amount_edit);
            }

            if (isset($_POST['delete_role'])) {
                $role_id = mysql_real_escape_string($_POST['role_id']);
                $res = $project->deleteRole($role_id);
            }
            
        }

        /* Prevent reposts on refresh */
        if (! empty($_POST)) {
            unset($_POST);
            header('Location: ' . $projectName);
            exit();
        }

        $edit_mode = false;
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit' && ( $is_runner || $is_payer || $is_owner)) {
            $edit_mode = true;
        }

        $this->write('project', $project);
        $this->write('edit_mode', $edit_mode);
        $this->write('is_owner', $is_owner);

        parent::run();
    }

    function addCodeReviewer($id, $user_id) {
        $this->view = null;
        try {
            $project = Project::find($id);
            $user = User::find($user_id);
            $request_user = User::find(getSessionUserId());
            if (! $project->getProjectId()) {
                throw new Exception('Not a project in our system');
            }
            if (!$request_user->getIs_admin() && !$project->isOwner($request_user->getId())) {
                throw new Exception('Not enough rights');
            }
            if (!$user->getId()) {
                throw new Exception('Not a user in our system');
            }
            if ($project->isProjectCodeReviewer($user->getId())) {
                throw new Exception('Entered user is already a Code Reviewer for this project');
            }
            if (! $project->addCodeReviewer($user->getId())) {
                throw new Exception('Could not add the user as a designer for this project');
            }
            $founder = User::find($project->getOwnerId());
            $founderUrl = SECURE_SERVER_URL . 'jobs#userid=' . $founder->getId();
            $data = array(
                'nickname' => $user->getNickname(),
                'projectName' => $project->getName(),
                'projectUrl' => Project::getProjectUrl($project->getProjectId()),
                'projectFounder' => $founder->getNickname(),
                'projectFounderUrl' => $founderUrl
            );
            if (! sendTemplateEmail($user->getUsername(), 'project-codereviewer-added', $data)) {
                error_log("ProjectController:addCodeReviewer: send email to user failed");
            }
            // Add a journal notification
            $journal_message = '@' . $user->getNickname() . ' has been granted *Review* rights for project **' . $project->getName() . '**';
            sendJournalNotification($journal_message);
            echo json_encode(array(
                'success' => true,
                'data' => 'Code Reviewer added successfully'
            ));
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo json_encode(array(
                'success' => false,
                'data' => $error
            ));
        }
    }
}

