<?php

require('bootstrap.php');

@set_time_limit(0);
ignore_user_abort(true);

bdCloudServerHelper_Helper_Template::handlePing();

header('Content-Type: application/json');
die('{"moreDeferred":false}');