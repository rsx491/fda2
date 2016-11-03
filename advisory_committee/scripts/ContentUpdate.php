#!/usr/bin/env drush
<?php
/**
parse xml files, load into Drupal
**/

require_once("/var/www/html/fda/advisory_committee/scripts/Utils.php");

$drupal_root = "/var/www/html/fda/";
$loader_root = "/var/www/html/fda/advisory_committee/";

$entity_manager = \Drupal::entityManager();
$bundles = $entity_manager->getBundleInfo('node');
print_r($bundles); print"\n"; exit;
if( !isset( $bundles['advisory_committee_content'] ) ){
        print "advisory content type missing\n"; exit;
}

//print_r($bundles['advisory_committee_content']); print"\n"; exit;


//clearAllFields('advisory_committee_content');













