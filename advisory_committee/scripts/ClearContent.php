#!/usr/bin/env drush
<?php
/**
parse xml files, load into Drupal
**/

$drupal_root = "/var/www/html/fda/";
$loader_root = "/var/www/html/fda/advisory_committee/";

$entity_manager = \Drupal::entityManager();
$bundles = $entity_manager->getBundleInfo('node');

if( !isset( $bundles['advisory_committee_folio'] ) ){
        print "Folio content type missing\n"; //exit;
}

print "This script will remove all advisory committee calendar content and metadata. disable this line to continue"; exit;

print "Finding all folio nodes to remove..\n";

/*
$ids = Drupal::entityQuery('node')->condition('type','advisory_folio_list_item')->execute();
print count($ids)." Folio List Items exist\n";
entity_delete_multiple('node', $ids);


$ids = Drupal::entityQuery('node')->condition('type','advisory_committee_content')->execute();
print count($ids)." Advisory Committee Content exist\n";
*/



