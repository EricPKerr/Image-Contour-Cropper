<?
/* Image Contour Cropper
 * Author: EricPKerr@gmail.com
 * License: MIT License
 * Copyright (C) 2012 Eric Kerr
 */

class ImageContourCropper {
  var $debug_contour = false;
  var $image;
  var $image_type;
  
  function load($filename, $retries = 0) {
    $image_info = @getimagesize($filename);
    if(!$image_info && $retries < $this->max_retries){
      sleep(.1);
      $this->load($filename, $retries + 1);
      return;
    }
    $this->image_type = $image_info[2];
    if( $this->image_type == IMAGETYPE_JPEG ) {
      $this->image = @imagecreatefromjpeg($filename);
    } elseif( $this->image_type == IMAGETYPE_GIF ) {
      $this->image = @imagecreatefromgif($filename);
    } elseif( $this->image_type == IMAGETYPE_PNG ) {
      $this->image = @imagecreatefrompng($filename);
    }
    if(!$this->image && $retries < $this->max_retries){
      sleep(.1);
      $this->load($filename, $retries + 1);
    }
  }
  
  function save($filename, $image_type=IMAGETYPE_JPEG, $compression=100, $permissions=null) {
    if( $image_type == IMAGETYPE_JPEG ) {
      imagejpeg($this->image,$filename,$compression);
    } elseif( $image_type == IMAGETYPE_GIF ) {
      imagegif($this->image,$filename);
    } elseif( $image_type == IMAGETYPE_PNG ) {
      imagepng($this->image,$filename);
    }
    if( $permissions != null) {
      chmod($filename,$permissions);
    }
  }
  
  function output($image_type=IMAGETYPE_JPEG) {
    if( $image_type == IMAGETYPE_JPEG ) {
      imagejpeg($this->image);
    } elseif( $image_type == IMAGETYPE_GIF ) {
      imagegif($this->image);
    } elseif( $image_type == IMAGETYPE_PNG ) {
      imagepng($this->image);
    }
  }
  
  function getWidth() {
    return imagesx($this->image);
  }
  
  function getHeight() {
    return imagesy($this->image);
  }
  
  function getScaledWidth($height)
  {
    $ratio = $height / $this->getHeight();
    return $this->getWidth() * $ratio;
  }
  
  function getScaledHeight($width)
  {
    $ratio = $width / $this->getWidth();
    return $this->getheight() * $ratio; 
  }
  
  function resizeToHeight($height) {
    $this->resize($this->getScaledWidth($height), $height);
  }
  
  function resizeToWidth($width) {
    $this->resize($width, $this->getScaledHeight($width));
  }
  
  function scale($scale) {
    $width = $this->getWidth() * $scale/100;
    $height = $this->getheight() * $scale/100;
    $this->resize($width,$height);
  }
  
  function resize($width,$height) {
    $new_image = imagecreatetruecolor($width, $height);
    imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
    $this->image = $new_image;
  }
  
  function thumbnail($width, $height)
  {
    if($this->getWidth() > $this->getHeight()){
      if($this->getScaledWidth($height) < $width) {
        $this->resizeToWidth($width);
      } else {
        $this->resizeToHeight($height);
      }
    } else {
      if($this->getScaledHeight($width) < $height) {
        $this->resizeToHeight($height);
      } else {
        $this->resizeToWidth($width);
      }
    }
    
    $source_width = $this->getWidth();
    $source_height = $this->getHeight();
    
    $im = imagecreatetruecolor($source_width, $source_height);
    imagecopyresampled($im, $this->image, 0, 0, 0, 0, $source_width, $source_height, $source_width, $source_height);
    if($this->debug_contour) imagejpeg($im, 'orig.jpg', 100);
    
    imagefilter($im, IMG_FILTER_CONTRAST, -30); // Increase the amount of contrast so edges pop out
    if($this->debug_contour) imagejpeg($im, 'contrast.jpg', 100);
    
    imagefilter($im, IMG_FILTER_EDGEDETECT); // Pretty solid this exists
    if($this->debug_contour) imagejpeg($im, 'edges.jpg', 100);
    
    $contour = array();
    $contour_threshold = 80;
    $half_width = $source_width / 2;
    
    for($x = 0; $x < $source_width; ++$x) {
      $contour[$x] = array();
      $contour_value = (1 - (abs($x - $half_width) / $half_width)) / 2; // put more emphasis on edges near x middle than near edge of image
      
      for($y = 0; $y < $source_height; ++$y) {
        $index = imagecolorat($im, $x, $y);
        $lum = array_sum(array_values(imagecolorsforindex($im, $index))) / 3;
        $is_edge = $lum <= $contour_threshold;
        $contour[$x][$y] = $is_edge ? $contour_value : 0;
        
        if($this->debug_contour) {
          $contour_color = $is_edge ? 0 : 255;
          imagesetpixel($im, $x, $y, imagecolorallocatealpha($im, $contour_color, $contour_color, $contour_color, 0));
        }
      }
    }
    
    if($this->debug_contour) imagejpeg($im, 'contour.jpg', 100);
    
    $contour_sum = array();
    $offset = 0;
    $max_density = 0;
    
    $new_image = imagecreatetruecolor($width, $height);
    imagefill($new_image, 0, 0, imagecolorallocate($new_image, 238, 238, 238));
    
    if($source_width <= $source_height) {
      for($y = 0; $y < $source_height; ++$y) {
        $contour_sum[$y] = 0;
        for($x = 0; $x < $source_width; ++$x) {
          $contour_sum[$y] += $contour[$x][$y];
        }
      }
      
      for($i = 0; $i < $source_height - $height; ++$i) {
        $contour_density = 0;
        for($j = 0; $j < $height; ++$j) {
          $contour_density += $contour_sum[$i + $j];
        }
        if($contour_density > $max_density) {
          $offset = $i;
          $max_density = $contour_density;
        }
      }
      
      imagecopy($new_image, $this->image, 0, 0, 0, $offset, $width, $height);
    }
    else
    {
      for($x = 0; $x < $source_width; ++$x) {
        $contour_sum[$x] = 0;
        for($y = 0; $y < $source_height; ++$y) {
          $contour_sum[$x] += $contour[$x][$y];
        }
      }
      
      for($i = 0; $i < $source_width - $width; ++$i) {
        $contour_density = 0;
        for($j = 0; $j < $width; ++$j) {
          $contour_density += $contour_sum[$i + $j];
        }
        if($contour_density > $max_density) {
          $offset = $i;
          $max_density = $contour_density;
        }
      }
      
      imagecopy($new_image, $this->image, 0, 0, $offset, 0, $width, $height);
    }
    $this->image = $new_image;
  }
}
?>