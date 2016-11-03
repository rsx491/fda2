#!/usr/bin/env drush
<?php
/**
Go through folio items, create folio metadata fields if needed, move fields there and link
**/

require_once('/var/www/html/fda/advisory_committee/scripts/Utils.php');


//make sure folio item has folio metadata reference
if(	!checkFieldExistsBundle('field_folio_meta','advisory_folio_list_item') ){
	addEntityReference('field_folio_meta', 'advisory_folio_list_item', 'Folio Metadata','advisory_folio_list_item_meta');
	print "Created field_folio_meta reference\n";
} else {
	print "field_folio_meta already set\n";
}

if(     !checkFieldExistsBundle('field_foliom_nodeid','advisory_folio_list_item_meta') ){
        addField('field_foliom_nodeid', 'advisory_folio_list_item_meta','string', 'Folio Item Nodeid',true);
        print "Created field_foliom_nodeid\n";
} else {
        print "field_foliom_nodeid already set\n";
}

//now  fetch all fields for folio list items to loop through and attach to  metadata if needed
$meta_fields = array(); //store all fields we moved to meta here
$skip_fields = array(
	'field_folio_file'=>1,'field_folio_href'=>1,
	'field_folio_image'=>1,'field_folio_link'=>1,
	'field_folio_name'=>1,'field_folio_meta'=>1,
	'field_folio_nodeid'=>1,'field_folio_text'=>1
);
$bundle_fields = \Drupal::entityManager()->getFieldDefinitions('node','advisory_folio_list_item');
        foreach($bundle_fields as $fname=>$field_config){
                if("field_folio_"!=substr($fname,0,12) ) continue;
		if(isset($skip_fields[$fname])) continue;
//                print "Found field ".substr($fname,12)."\n"; continue;
		array_push($meta_fields, $fname);
		continue;
                try {
			if(checkFieldExistsBundle($fname,'advisory_folio_list_item_meta',true)){
				print "Field ".$fname." is attached to meta\n";	continue;
			} else { 
				print "Field ".$fname." is not created\n";
			} 
                } catch (Exception $e){
                        print "Exception when trying to attach $fname: ".$e."\n";
                }
        }

//now loop through folio list items to create an attached meta record and delete that data from the item
$list_items = loadRecordsByContentType('advisory_folio_list_item');
print "Fetched ".count($list_items)." list items..\n";
foreach($list_items as $li){
	print print_r($li->field_folio_nodeid->value,true)."\n";
//	print "Fields: \n".print_r($li->getFieldDefinition('field_folio_meta'),true)."\n"; exit;
	$meta_prev = loadRecordByField('advisory_folio_list_item_meta','field_foliom_nodeid',$li->field_folio_nodeid->value);
	if(!$meta_prev){
		print "No existing meta record\n";
		$meta_r = array(
			'nid'=>null,
			'type'=>'advisory_folio_list_item_meta',
			'status'=>true,
			'body'=>'',
			'title'=>'Metadata for Folio List Item '.$li->field_folio_nodeid->value,
			'field_foliom_nodeid'=>$li->field_folio_nodeid->value
		);
		foreach($meta_fields as $fname){
			$meta_r[$fname] = $li->$fname->value;
		}
		print "MetaR: ".print_r($meta_r,true)."\n";
		$meta_prev = Drupal::entityManager()->getStorage('node')->create($meta_r);
		$meta_prev->save();
		print "Saved meta_r: ".$meta_prev->id()."\n";
	} else {
		print "Found existing meta record\n";
	}
	try {
	$li->set('field_folio_meta', array(array('target_id'=>$meta_prev->id())) );
	//clear meta fields from li item
	foreach($meta_fields as $fname){
	//	print "Cleared $fname\n";
		$li->set($fname, null);
	}
	$li->save();
	} catch(Exception $e){
		print "Exception updating li: ".$e->getMessage()."\n";
	}
	//print "List item updated\n"; exit;
	
}


//no go through meta records and delete field_configs attached to list items
foreach($meta_fields as $fname){
	try {
		removeField($fname, 'advisory_folio_list_item');
		print "Removed field: ".$fname."\n";
	} catch(Exception $e){
		print "Could not remove field: ".$fname." - ".$e->getMessage()."\n";
	}
}

print "Finished..\n";



