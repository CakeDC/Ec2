<?php
/**
 * Copyright 2011, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2011, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

// Load configuration
Configure::load('Amazon');

// Set defines required for Amazon Web Services
foreach (Configure::read('Amazon') as $key => $value) {
	define($key, $value);
}
