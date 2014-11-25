<?php

class JobsView extends View {
    public $title = 'Jobs - Worklist';
    public $stylesheets = array(
        'css/jobs.css'
    );
    public $scripts = array(
        'js/jobs.js'
    );

    public function render() {
        $this->page = $this->read('page');
        $this->review_only = $this->read('review_only');
        $this->filter = $this->read('filter');
        return parent::render();
    }

    public function projectSelect() {
        return $this->filter->getProjectSelectbox('All projects', true);
    }

    public function statusSelect() {
        $filter = $this->read('filter');
        $allDisplay = "All Status";
        $req_status = $this->read('req_status');
        if ($this->currentUser['is_runner']) {
            $status_array = array(
                "Draft",
                "Suggestion",
                "Bidding", 
                "In Progress",
                "QA Ready",
                "Code Review",
                "Merged",
                "Done", 
                "Pass"
            );
        } else {
            $status_array = array(
                "Suggestion",
                "Bidding", 
                "In Progress",
                "QA Ready",
                "Code Review", 
                "Merged",
                "Done", 
                "Pass"
            );
        }

        $box = '<select id="statusCombo" name="status" multiple="multiple" data-placeholder="All status" title="All status">';
        //$box .= '<option value="ALL"' . (($filter->inStatus("ALL") || ($filter->inStatus(""))) ? ' selected="selected"' : '') .' > ' . $allDisplay . '</option>';
        
        foreach ($status_array as $status) {
            $selected = '';
            if (empty($req_status)) {
                if ($filter->inStatus($status)) {
                    $selected = ' selected="selected"';
                }
            } else {
                switch ($status) {
                    case 'Draft':
                        if ($req_status == 'draft') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Bidding':
                        if ($req_status == 'bidding') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'In Progress':
                        if ($req_status == 'in progress') {
                            $selected = ' selected="selected"';
                        }
                        //must be left in tact for the Jobs Underway link to produce accurate data
                        //holds true for functional and review below
                        if ($req_status == 'underway') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'QA Ready':
                        if ($req_status == 'qa ready') {
                            $selected = ' selected="selected"';
                        }
                        if ($req_status == 'underway') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Code Review':
                        if ($req_status == 'review') { 
                            $selected = ' selected="selected"';
                        }
                        if ($req_status == 'underway') { 
                            $selected = ' selected="selected"';
                        }
                        if ($req_status == 'needs-review') {
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Merged':
                        if ($req_status == 'completed') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                    case 'Done':
                        if ($req_status == 'done') { 
                            $selected = ' selected="selected"';
                        }
                        if ($req_status == 'completed') { 
                            $selected = ' selected="selected"';
                        }
                        break;
                }
            }
            $box .= '<option value="' . $status . '"' . $selected . '>' . $status . '</option>';
        }
        
        //$box .= '<option value="CheckDone">Done</option>';
        $box .= '</select>';
        return $box;
    }
}
