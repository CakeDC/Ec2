<?php
// Load configuration
Configure::load('Amazon');

// Set defines required for Amazon Web Services
foreach (Configure::read('Amazon') as $key => $value) {
	define($key, $value);
}
