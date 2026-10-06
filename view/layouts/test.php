<?php
date_default_timezone_set('America/Santiago');
$date = new DateTime();
$date = $date->format('Y-m-d H:i:s');

echo $date;