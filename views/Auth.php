<?php

class AuthView extends View {
    public $title = 'Welcome to the Worklist!';
    public $stylesheets = array(
        'css/auth.css'
    );
    public $scripts = array(
        'js/auth.js'
    );

    public $jumbotron = "";

    public function render() {
        $safeLink = './github/safe/' . $this->redir;
        $this->jumbotron = "
            <h2>Almost done...</h2>
            <p>
              Now you're just a step closer.
            </p>
            <p>
              Please let us know whether you are a new user in the Worklist
              or an existing one by providing your e-mail address.
            </p>
            <p>Can't login to your existing account? Try the <a href='" . $safeLink . "'>safe-mode log in</a></p>";

        return parent::render();
    }

    public function defaultUsername() {
        return $this->read('default_username');
    }

    public function defaultLocation() {
        return $this->read('default_location');
    }

    public function accessToken() {
        return $this->read('access_token');
    }
}
