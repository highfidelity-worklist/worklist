<?php

class HelpView extends View {
    public $title = 'Help / FAQ - Worklist';

    public $stylesheets = array(
        'css/worklist.css'
    );

    public $navLink = 'nav a[href$="/help"]';
}