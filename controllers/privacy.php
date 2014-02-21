<?php

class PrivacyController extends Controller {
	public function run() {
		Session::check();
		parent::run();
	}
}
