<?php

require('imagecontourcropper.class.php');

echo "Image URL: ";
$handle = fopen ("php://stdin","r");

$i = new ImageContourCropper;
$i->debug_contour = true;
$i->load(trim(fgets($handle)));
$i->thumbnail(274, 167);
$i->save('thumb.jpg');

?>