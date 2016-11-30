<?php

require ('bootstrap.php');

bdCloudServerHelper_Helper_Template::handlePing();

header('Content-Type: application/json');
die('{"moreDeferred":false}');