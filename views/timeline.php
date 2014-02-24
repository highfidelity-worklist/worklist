<?php

class TimelineView extends View {
	public $layout = 'emptybody';

	public $title = '';

	public $stylesheets = array(
		'css/timeline.css'
	);

	public $scripts = array(
        'https://maps.googleapis.com/maps/api/js?v=3&sensor=false',
        'js/spin.js',
        'js/markerclusterer/markerclusterer.js',
        'js/timeline.js'
	);
}