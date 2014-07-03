<?php

class FeeController extends JsonController {
    public function run($action, $param = '') {
        $method = '';
        switch($action) {
            case 'info':
            case 'setPaid':
                $method = $action;
                break;
            default:
                $method = 'info';
                $param = $action;
                break;
        }
        $params = preg_split('/\//', $param);
        call_user_func_array(array($this, $method), $params);
    }

    public function info($id) {
        if (!$fee = Fee::getFee($id)) {
            return $this->setOutput(array(
                'success' => false,
                'message' => 'Invalid fee id'
            ));
            return;
        }
        return $this->setOutput(array(
            'id' => $fee['id'],
            'paid' => $fee['paid'],
            'notes' => $fee['notes']
        ));
    }

    public function setPaid($id, $paid) {
        try {
            $user = User::find(getSessionUserId());

            // Check if we have a payer
            if (!$user->isPayer()) {
                throw new Exception('Nothing to see here. Move along!');
            }

            // Get clean data
            $paid = $paid ? true : false;
            $notes = trim($_POST['notes']);
            if (!$notes) {
                throw new Exception('You must write a note!');
            }

            $fund_id = Fee::getFundId($id);

            // Exit of this script
            if (!Fee::markPaidById($id, $user->getId(), $notes, $paid, false, $fund_id)) {
                throw new Exception('Payment Failed!');
            }
            /* Only send the email when marking as paid. */
            if ($paid) {
                $fee = Fee::getFee($fee_id);
                $summary = getWorkItemSummary($fee['worklist_id']);
                $fee_user = User::find($fee['user_id']);
                $subject = "Worklist.net paid you " . $fee['amount'] ." for ". $summary;
                $body =
                    "Your Fee was marked paid.<br/>" .
                    "Job <a href='" . SERVER_URL . $fee['worklist_id'] . "'>#" . $fee['worklist_id'] . ': ' . $summary . '</a><br/>' .
                    "Fee Description : " . nl2br($fee['desc']) . "<br/>" .
                    "Paid Notes : " . nl2br($notes) . "<br/><br/>" .
                    "Contact the job Designer with any questions<br/><br/>Worklist.net<br/>";
                if(!send_email($fee_user->getUsername(), $subject, $body)) {
                    error_log("FeeController::setPaid: send_email failed");
                }
            }
            return $this->setOutput(array(
                'success' => true,
                'notes' => 'Payment has been saved!'
            ));
        } catch (Exception $e) {
            return $this->setOutput(array(
                'success' => false,
                'notes' => $e->getMessage()
            ));
        }
    }
}