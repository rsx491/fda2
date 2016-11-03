#!/usr/bin/env drush
<?php
/**
parse xml files, load into Drupal
**/

$drupal_root = "/var/www/html/fda/";
$loader_root = "/var/www/html/fda/advisory_committee/";

$entity_manager = \Drupal::entityManager();
$bundles = $entity_manager->getBundleInfo('node');

//check if module for advisory content type is already in drupal directory
//if( file_exists($drupal_root."modules/advisory_committee/advisory_create_content_type") ){

if( isset( $bundles['advisory_committee_content'] ) ){
	print "Advisory Committee content type module already exists, skipping creation\n";
} else {
	print "Copying advisory committee content type module and activating it..\n";
	//$cmd = shell_exec("cp -R ".$loader_root."advisory_committee ".$drupal_root."/modules/.");
	//$cmd = shell_exec("drush en advisory_create_content_type");
	print "done\n";
}

if( isset( $bundles['advisory_committee_folio'] ) ){
	print "Advisory Committee Folio module already exists, skipping creation\n";
} else {
	print "Copying advisory committee folio module and activating it..\n";
	$cmd = shell_exec("cp -R ".$loader_root."advisory_committee/advisory_committee_folio ".$drupal_root."/modules/advisory_committee/.");
	$cmd = shell_exec("drush en advisory_committee_folio");
	print "done\n";
}





//$nodeStorage = $entity_manager->getStorage('test_type');
//$ids = $nodeStorage->getQuery()->condition('status', 1)->execute();
$ids = Drupal::entityQuery('node')->condition('type','advisory_committee_content')->execute();
$folio_ids = Drupal::entityQuery('node')->condition('type','advisory_committee_folio')->execute();
$slot_ids = Drupal::entityQuery('node')->condition('type','advisory_committee_folio_slot')->execute();
print count($ids)." Advisory Committee content type nodes already exist\n";
print count($folio_ids)." Folios and ".count($slot_ids)." folio list items already exist\n";

exit;

//updateDates($ids); exit;

// This will remove ALL advisory_committee_content records if you enable

/*
deleteRecords();
$ids = Drupal::entityQuery('node')->condition('type','advisory_committee_content')->execute();
print "Now ".count($ids)." exist\n"; exit;
*/

/** add datetime field **/
//removeField('field_advisory_datetime','advisory_committee_content');
//addField('field_advisory_datetime','advisory_committee_content','datetime','Document DateTime');
//print "added field for datetime\n"; exit;

/* Fetch list of views */

//$views = \Drupal::service('entity.manager')->getStorage('view')->getAllViews();
//$views = \Drupal\views\Views::pluginList();
//print "VIEWS\n".print_r($views,true)."\n"; exit;


/* Convering field formatters to plain text */
//convertFields();
//exit;


/* create a field config and then save it and retrieve it
$field_storage = entity_create('field_storage_config', array(
	'field_name' => 'field_test_sample',
	'entity_type' => 'node',
	'type' => 'string'
	));
$field_storage->save();

$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node','field_test_sample');
print "test_text:\n".print_r($field_storage,true)."\n";
*/


/*
$ids = Drupal::entityQuery('field_storage_config')->execute();
print_r($ids);

exit;
*/

//use to get current field definitions for our content type
//$test = $entity_manager->getFieldDefinitions('node','test_type');
//print_r($test['field_test_text']);

//$node_storage = $entity_manager->getStorage('node');
//print( get_class($node_storage));

//$field = array( 'name'=>'testfield', 'entity_type'=>'test_type','type'=>'text');
//$entity_manager->getStorage('field_storage_config')->create($field)->save();


//print "done\n"; exit;
print "Loading loadfile..\n";
$loadfile_path = $loader_root."LOADFILE.xml";
$xml = new XMLReader();
$xml->open($loadfile_path);
$loadfile_obj = xml2assoc($xml, "root");
$xml->close();

//actual results array is nested several children deep
$resultset = $loadfile_obj[0]['val'][0]['val'][0]['val'][0]['val'][0]['val'];
print "Found ".count($resultset)." rows in resultset\n";

//go through all xml files and read in data + fields
$fields_added = array();
$records = array();

//add doctitle and docname fields
if(!checkFieldExists('field_advisory_docname') ){
	addField('field_advisory_docname','advisory_committee_content','string','dDocName');
}
if(!checkFieldExists('field_advisory_doctitle') ){
	addField('field_advisory_doctitle','advisory_committee_content','string','dDocTitle');
}


//loop through records, add any fields needed and save to an array
$loadfile_fields = array();
foreach($resultset as $record){
	$title = isset($record['atr']['dDocTitle']) ? $record['atr']['dDocTitle'] : '';
	$newRecord = array( 'field_advisory_doctitle' => $title, 'field_advisory_docname'=>$record['atr']['dDocName'] );
	foreach($record['val'] as $i=>$field){
		if( $field['atr']['name']==='primaryFile:path' ){
			//continue; //skip loading xml files are already loaded
			$xml->open($loader_root.$field['val']);
			$field_obj = xml2assoc($xml, "root");
			$xml->close();
			foreach($field_obj[0]['val'] as $xi=>$xfield){
				if( 'wcm:' == substr($xfield['name'],0,4) ){
					$field_name = 'field_advisory_'.strtolower($xfield['atr']['name']);
					$newRecord[ $field_name ] = $xfield['val'];
					if(!isset($fields_added[ $xfield['atr']['name'] ]) ){
						$fields_added[ $xfield['atr']['name'] ] = 1;
					}
					if(!checkFieldExists($field_name) ){
						//$field_type = ($field_name=='field_advisory_maincontent')?'text_long': 'string';
						addField($field_name, 'advisory_committee_content','text_long', $xfield['atr']['name'] );
					}
					$loadfile_fields[$field_name] = 'text_long';
				}
			}
		} else if( isset($field['atr']['name']) ) {
			$field_name = 'adv_'.strtolower($field['atr']['name']);
			if( strlen($field_name) > 32) {
				$field_name = substr($field_name, 0,31);
			}
			$field_type = ($field_name=='adv_xdescription'||$field_name=='adv_xkeyword')?'text_long':'string';
			if(!checkFieldExists($field_name) ){
				//$field_type = ($field_name=='adv_xdescription'||$field_name=='adv_xkeyword')?'text_long':'string';
				if($field_type=='string'){
					addField($field_name,'advisory_committee_content','string', $field['atr']['name']);
				} else {
					addField($field_name, 'advisory_committee_content',$field_type, $field['atr']['name']);
				}	
			}
			$loadfile_fields[$field_name]=$field_type; 

			$newRecord[ $field_name ] = $field['val'];
		} else {
			print "x";
		}
	}
	array_push($records, $newRecord);
	
}

print count($records)." Records read in from xml\n";

//print_r($loadfile_fields); print "\n"; exit;
//resetFields($loadfile_fields); exit;
//now create the node entities for each record making sure it doesnt already exist first
foreach($records as $i=>$record){
	print "Inserting record: ".$record['field_advisory_doctitle']."... ";
	if( checkRecordExists( $record['field_advisory_docname'] ) ){
		print "Already exists, updating extra fields..\n";
		updateRecordFields($record, $loadfile_fields);

		print "Already Exists, Skipping\n";
		continue;
	}
	//continue;
	//insertRecord($record);
	print "Done\n";
	
}

exit("\nDone\n");

function addPlainField($field_name, $bundle_name, $field_label){
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
	if($field_storage){
		print "Cannot add field: $field_name , field_storage_config already exists\n"; 
		return;
	}
	$field_storage = entity_create('field_storage_config', array(
                'field_name' => $field_name,
                'entity_type' => 'node',
                'type' => 'string'
        ));
        $field_storage->save();

        $field = entity_create('field_config', array(
                'entity_type' => 'node',
                'field_name' => $field_name,
                'field_storage' => $field_storage,
                'bundle' => $bundle_name,
                'type' => 'string',
                'label' => $field_label,
        ));
        $field->save();
        print "Saved field: ".$field_name."\n";
        entity_get_form_display('node', $bundle_name, 'default')
                ->setComponent( $field_name, array(
                        'type' => 'string_textfield',
                        'weight' => 1,
                ))
                ->save();
        $display_info = entity_get_form_display('node', $bundle_name, 'default')->getComponent($field_name);
}

function resetFields($loadfile_fields){
	foreach($loadfile_fields as $fname=>$ftype){
		print $fname.": ";
		$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$fname);
		$field_config = Drupal::entityManager()->getStorage('field_config')->loadByProperties( array('field_name'=>$fname) );
		if($field_config && isset($field_config['node.advisory_committee_content.'.$fname]) ){
                        print "Field config found, deleting.. ";
                        $field_config['node.advisory_committee_content.'.$fname]->delete();
                }

		if($field_storage){
		 	print "storage found, deleting.. "; 
			try {
				$field_storage->delete();
			} catch (Exception $e) {
				print "exception caught ";
			}
		}
		print "\n";
	}
}

function removeField($field_name,$bundle_name){
	$field_config = Drupal::entityManager()->getStorage('field_config')->loadByProperties( array('field_name'=>$field_name) );


	//print "Field_config: ".print_r($field_config['node.advisory_committee_content.'.$field_name],true)."\n"; return;
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
	//print "Field Storage: ".print_r($field_storage, true)."\n";
	$field_config['node.advisory_committee_content.'.$field_name]->delete();
	$field_storage->delete();
	print "Deleted $field_name \n";
}

function convertFields(){

	//addPlainField('adv_test_1','advisory_committee_content','Advisory Test Field 1');
	//removeField('adv_test_1','advisory_committee_content');
		

	$bundle_fields = \Drupal::entityManager()->getFieldDefinitions('node','advisory_committee_content');
	$loadfile_fields = array();
	foreach($bundle_fields as $fname=>$field_config){
		if("adv"!=substr($fname,0,3)&&"field_advisory"!=substr($fname,0,14)) continue;
		//print $fname."\n"; continue;
		//if($fname !="adv_test_1") continue;
		$label = $field_config->label();
		try {
			removeField($fname,'advisory_committee_content');
		} catch (Exception $e){
			print "Exception when trying to remove $fname: ".$e."\n";
		}
		addPlainField($fname,'advisory_committee_content',$label);
		print "Recreated $fname\n";
		//print $fname."\n".print_r($field_config,true)."\n"; exit; 
	}
	exit;
	foreach($loadfile_fields as $field_name){
        	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
		$field_storage = $field_storage->toArray();
		print $field_name.": ".$field_storage['type']."\n";

		//get field_config instances and convert type
		$field_configs = \Drupal::entityManager()->getStorage('field_config')->loadByProperties( array( 'field_name' => $field_name) );
		foreach($field_configs as $field_config){
			$new_field = $field_config->toArray();
			print "Config: ".$new_field['field_type']."\n";
		}
	
		continue;
		//convert storage to string
		$field_storage['type'] = ['string'];
		$new_field_storage = \Drupal\field\Entity\FieldStorageConfig::create($field_storage);
		$new_field_storage->original = $field_storage;
		$new_field_storage->enforceIsNew(FALSE);
		//print "new field: ".print_r($new_field_storage,true)."\n"
		//$new_field_storage->save();
	}

}

function deleteRecords(){
	$nids = Drupal::entityQuery('node')->condition('type','advisory_committee_content')->execute();
	entity_delete_multiple('node', $nids);

}

function insertRecord($record){
	$record['nid'] = NULL;
	$record['type'] = 'advisory_committee_content';
	$record['status'] = true;
	$record['body'] = $record['field_advisory_maincontent'];
	$record['title'] = $record['field_advisory_doctitle'];
	$node = Drupal::entityManager()->getStorage('node')->create($record);
	$node->save();
}

function checkRecordExists($record_name){
	$nids = Drupal::entityQuery('node')->condition('type','advisory_committee_content')
		->condition('field_advisory_docname',$record_name)
		->execute();

	if( $nids && count($nids) > 0){
		return true;
	}
	return false;
}

function updateRecordFields($record, $loadfile_fields){
	//load record and update all fields listed in loadfile_fields
	$entity = loadRecordByName($record['field_advisory_docname']);
	print "Updating record fields for [".$record['field_advisory_docname']."]: .. \n";

	if(isset($record['adv_dindate'])){
		print "Converting record date: ".$record['adv_dindate']." ";
		$dt_exploded = explode(' ',$record['adv_dindate']);
		$timestamp = strtotime($dt_exploded[0]);
		$record['field_advisory_datetime'] = gmdate('Y-m-d',$timestamp);
		print "timestamp: $timestamp - gmdate: ".$record['field_advisory_datetime']."\n";
		$entity->set('field_advisory_datetime', $record['field_advisory_datetime']);
		$entity->save();
	}

	foreach($loadfile_fields as $field_name=>$v){
		if( isset($record[$field_name]) && ("field"==substr($field_name,0,5)) ){
			//print $field_name.": ".$record[$field_name]."\n";
			$entity->set($field_name, $record[$field_name]);
		}
	}
	$entity->save();
	print ".. updated\n";

}

function updateDates($ids){
	$id_keys = array_keys($ids);
	$entities = \Drupal::entityManager()->getStorage('node')->loadMultiple($id_keys);
	print "Updating dates on ".count($entities)." documents\n";
	foreach($entities as $entity){
		if($entity->adv_xeditiondate->value){
                print "Converting record date: ".$entity->adv_xeditiondate->value." ";
                $dt_exploded = explode(' ',$entity->adv_xeditiondate->value);
                $timestamp = strtotime($dt_exploded[0]);
                $field_advisory_datetime = gmdate('Y-m-d',$timestamp);
                print "timestamp: $timestamp - gmdate: ".$field_advisory_datetime."\n";
                $entity->set('field_advisory_datetime', $field_advisory_datetime);
                $entity->save();
        	}
	}
}

function loadRecordByName($record_name){
	$nids = Drupal::entityQuery('node')->condition('type','advisory_committee_content')
		->condition('field_advisory_docname',$record_name)
		->execute();
	if($nids && count($nids)>0){
		$nidkeys = array_keys($nids);
		return \Drupal::entityManager()->getStorage('node')->load($nids[$nidkeys[0]]);
	} else {
		return null;
	}
}


function checkFieldExists($field_name){
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
	//if($field_storage){ $field_storage->delete(); }
	return ($field_storage) ? true : false;
}

function addField($field_name, $bundle_name, $field_type, $field_label){
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
	if($field_storage){
		print "Field storage: ".$field_name." already exists. attempting to delete to recreate\n";
		//$field_storage->delete();
	} else {
	$field_storage = entity_create('field_storage_config', array(
    		'field_name' => $field_name,
    		'entity_type' => 'node',
    		'type' => $field_type
  	));
	}
  	$field_storage->save();
	
	$field = entity_create('field_config', array(
    		'entity_type' => 'node',
    		'field_name' => $field_name,
    		'field_storage' => $field_storage,
    		'bundle' => $bundle_name,
		'type' => $field_type,
		//'format' => 'full_html',
    		'label' => $field_label,
  	));
  	$field->save();
	print "Saved field: "+$field_name+"\n";
	 
	
	entity_get_form_display('node', $bundle_name, 'default')
		->setComponent( $field_name, array(
			'type' => 'text_textarea',
			'weight' => 1,
			'format' => ($field_type!='string')?'full_html':null,
			'settings' => array('text_processing'=>0)
		))
		->save();
	$display_info = entity_get_form_display('node', $bundle_name, 'default')->getComponent($field_name);
	
	//print $field_name.": ".print_r($display_info,true)."\n";
	
}

//recursively parse xml nodes to return an associative array
function xml2assoc(&$xml){
    $assoc = NULL;
    $n = 0;
    while($xml->read()){
        if($xml->nodeType == XMLReader::END_ELEMENT) break;
        if($xml->nodeType == XMLReader::ELEMENT and !$xml->isEmptyElement){
            //print "*".$xml->name."\n";
            $assoc[$n]['name'] = $xml->name;
            if($xml->hasAttributes) while($xml->moveToNextAttribute()) $assoc[$n]['atr'][$xml->name] = $xml->value;
            $assoc[$n]['val'] = xml2assoc($xml);
            $n++;
        }
        else if($xml->isEmptyElement){
            $assoc[$n]['name'] = $xml->name;
            if($xml->hasAttributes) while($xml->moveToNextAttribute()) $assoc[$n]['atr'][$xml->name] = $xml->value;
            $assoc[$n]['val'] = "";
            $n++;
        }
        else if($xml->nodeType == XMLReader::TEXT) $assoc = $xml->value;
    }
    return $assoc;
}

