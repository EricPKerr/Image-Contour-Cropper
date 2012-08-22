Image Contour Cropper
- This class crops an image to specified dimensions by whatever section in the image has the greatest amount of contour. It is mostly useful for creating consistent thumbnails where the input images have varying size constraints.

Basic Usage:

``$i = new ImageContourCropper;
$i->debug_contour = true;
$i->load($external_file_url);
$i->thumbnail(274, 167);
$i->save('thumb.jpg');``

![Demo](https://raw.github.com/EricPKerr/Image-Contour-Cropper/master/demo/combined.jpg)