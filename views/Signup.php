<?php

class SignupView extends View {
    public $layout = 'NewWorklist';
    public $title = 'Sign Up to the Worklist';

    public $stylesheets = array(
        'css/signup.css'
    );

    public $scripts = array(
        'js/sendlove.js',
        'js/skills.js',
        'js/userSkills.js',
        'js/signup.js'
    );

    public function render() {
        $this->confirm_txt = $this->read('confirm_txt');
        $this->input = $this->read('input');
        return parent::render();
    }

    public function errorFlag() {
        $error = $this->read('error');
        return $error->getErrorFlag == 1;
    }

    public function errorMessages() {
        $error = $this->read('error');
        $messages = $error->getErrorMessage();
        $ret = array();
        foreach($messages as $msg) {
            $ret[] = array('text' => $msg);
        }
        return $ret;
    }

    public function countrySelectBox() {
        global $countrylist;
        $ret = '<select id="country" name="country" style="width:274px">';
        $input = $this->read('input');
        if (empty($input['country']) || $input['country'] == '--') {
            //$selected not set by this point, we want to default so do that
            $ret .= '<option value="US">United States -- Default</option> <option disabled="disabled">-----------</option>';
        }
        foreach ($countrylist as $code => $cname) {
            $selected = ($input['country'] == $code) ? "selected=\"selected\"" : "";
            $ret .= '<option value="' . $code . '" ' . $selected . '>' . $cname . '</option>';
        }
        $ret .= '</select>';
        return $ret;
    }

    public function timezoneSelectBox() {
        global $timezoneTable;
        $ret .= '<select id="timezone" name="timezone">';
        foreach ($timezoneTable as $key => $value) {
            $ret .= '<option value = "' . $key . '">' . $value . '</option>';
        }
        $ret .= '</select>';
        return $ret;
    }
}