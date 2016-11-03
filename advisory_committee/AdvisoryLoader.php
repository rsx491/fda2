#!/usr/bin/env drush
<?php
/**
parse xml files, load into Drupal
**/


$drupal_root = "/var/www/html/fda2/";
$loader_root = "/home/ubuntu/advisory_committee/";
require_once($loader_root.'scripts/Utils.php');

print "Running testimonial import..\n"; require_once($loader_root.'scripts/Testimonials.php'); exit;

//print "Clearing old content..\n"; require_once($loader_root.'scripts/ClearContent.php'); exit;
print "Moving content fix\n"; require_once($loader_root.'scripts/FixMoveContent.php'); exit;

print "Checking module is enabled..\n";
require_once($loader_root.'scripts/EnableModule.php');

print "Setting up predefined fields..\n";
require_once($loader_root.'scripts/PredefinedFields.php');



$loadfile_results = parseLoadFile($loader_root.'LOADFILE.xml');
$parsed = parseContentXML($loader_root, $loadfile_results);
print "Parsed ".count($parsed['records'])." records from xml\n";
//print "Loadfile fields: \n".print_r($parsed['loadfile_fields'],true)."\n";

print "Configuring metadata fields..\n";
addMetaFields($parsed['loadfile_fields'], $xml_fields);


print "Creating content + metadata records..\n";
require_once($loader_root.'scripts/InsertContent.php');
parseRecords($parsed, $xml_fields, $loader_root);



