#!/usr/bin/env drush
<?php
/**
parse xml files, load into Drupal
**/

$drupal_root = "/var/www/html/fda/";
$loader_root = "/var/www/html/fda/advisory_committee/";

//$entity_manager = \Drupal::entityManager();


/** check that target node ids are in the list of values, if not add it and return modified array **/
function addIdToArray($fvalues, $newId,$valname='target_id'){
	//print "Checking array for id: ".$newId."\n";
	$found = false;
	foreach($fvalues as $i=>$targ){
		if($targ && $targ[$valname] && $targ[$valname]==$newId){
			$found=true; break;
		}
	}
	if($found) return $fvalues;
	array_push($fvalues, array($valname=>$newId) );
	return $fvalues;
	
}


/** create the individual folio item entries and connect them to their matching content->field reference **/
function addFolioItems($content_items){
	print "Adding list items to ".count($content_items)." content pages\n";
	$content_updated=0; $items_added=0;
	foreach($content_items as $citem){
		//print_r($citem['props']); print"\n"; break;
		print $citem['props']['docname']." - ".$citem['props']['name']." has ".count($citem['list_items'])." list items\n";	
		$cnode = loadRecordByField('advisory_committee_content','field_advisory_docname', $citem['props']['docname']);
		if(!$cnode){
			print "Couldn't find content node!\n"; continue;
		}
		$fname = 'advf_'.strtolower($citem['props']['name']);
		$fvalues = $cnode->get($fname)->getValue();
		//print "Current value: ".print_r($fvalues,true)."\n"; 
		foreach($citem['list_items'] as $li){
			
			$li_prev = loadRecordByField('advisory_folio_list_item','folio_nodeid', $li['nodeid']);
			if($li_prev){
				print "folio list item ".$li['nodeid']." already exists!\n";
				//$li_prev->delete();
				$fvalues = addIdToArray($fvalues, $li_prev->id());
				continue;
			}
			$record = array(
				'nid'=>null,
				'type'=>'advisory_folio_list_item',
				'status'=>true,
				'body'=>'',
				'title'=>'item'
			);
			foreach($li as $propname=>$propvalue){
				$propname = 'folio_'.strtolower($propname);
				$record[$propname]=$propvalue;
			}
			$record['title'] = $record['folio_ddoctitle']?$record['folio_ddoctitle']:($record['folio_name']?$record['folio_name']:'li');
			$record['folio_link'] = array(
                                        'uri' => '',
                                        'title' => $record['folio_ddoctitle'],
                                        'options' => array()
                                );
			if( $record['folio_dformat']=='text/html'
			||$record['folio_dformat']=='Application/xml'
			||$record['folio_dformat']=='idcmeta/html'
			||(!isset($record['folio_dformat'])&&$record['folio_xlinkwebaddress'])
			){
				$record['folio_link'] = array(
					'uri' => $record['folio_xlinkwebaddress'],
					'title' => $record['folio_ddoctitle'],
					'options' => array()
				);
			} else if(!isset($record['folio_dformat']) && $record['folio_docurl'] ) {
				$record['folio_link'] = array(
                                        'uri' => $record['folio_docurl'],
                                        'title' => $record['folio_ddoctitle'],
                                        'options' => array()
                                );

			} else if($record['folio_downloadurl']) {
				$record['folio_link']['uri'] = $record['folio_downloadurl'];
			} else {
				print "CANT FIND LINK\n";  print_r($record); print"\n"; continue;
			}
			//print_r($record);print"\n";return;
			//save record then add it to array of values
			
			$node = Drupal::entityManager()->getStorage('node')->create($record);
			$node->set('folio_link', array('uri'=>$record['folio_link']['uri'],'title'=>$record['folio_link']['title']) ); 
   			$node->save();
			$fvalues = addIdToArray($fvalues, $node->id() );
			$items_added++;
			//print "SAVED ITEM ID: ".$node->id()."\n";
			//$linkval = $node->get('folio_link')->getValue();
			//print "LINK: ".print_r($record['folio_link'],true)."\n--\n".print_r($linkval,true)."\n";
			
		}

		//set value of list field to ids of saved nodes
		$cnode->set($fname, $fvalues);
		$cnode->save();
		$content_updated++;
		//print "CHECK ".$cnode->get('field_advisory_doctitle')->getValue()." \n"; break;

	}
	print "Finished with ".$items_added." folio items added to ".$content_updated." content pages\n";
}


/** add fields required by folio list items **/
function addFolioFields($fields){
	foreach($fields as $oname=>$fi){
		$fname = 'folio_'.strtolower($oname);
		if(checkFieldExists($fname)){
			print $fname. " is already set\n"; 
			removeField($fname,'advisory_folio_list_item');
			continue;
		}
		addField($fname, 'advisory_folio_list_item', $fi, $oname, true);
	}
}

/** fetch a record by id and a specific field name value  and display it **/
function viewRecordField($recordId, $fieldName){
	$entity = loadRecordById($recordId);
	if(!$entity){
		print "Could not find entity ID:".$recordId."\n"; return;
	}
	//$entity->set($fieldName, array( array('target_id'=>1) , array('target_id'=>2) )  );
	$field_list = $entity->get($fieldName);
	if(!$field_list){ print "Could not fetch field: ".$fieldName."\n"; return; }
	print "Record: ".$entity->getTitle()."\n".$fieldName.": ".print_r($field_list->getValue(),true)."\n";	
	
}



/** go through list of content field names and remove them if they exist, then add them as content entity references **/
function addContentFields($fieldNames){
	foreach($fieldNames as $fname=>$tmp){
		$oldname = $fname; //print $oldname."\n"; continue;
		$fname = 'advf_'.strtolower($fname);
		if( checkFieldExists($fname) ){
			
			removeField($fname,'advisory_committee_content');
		}
		//addEntityReference($fname, 'advisory_committee_content', $oldname, 'advisory_folio_list_item'); 		
	}
}

/** add the inline_entity module as the form view for this field **/
function addInlineFormDisplays($contentFields){
	$fixedNames = array();
	$bundle_fields = \Drupal::entityManager()->getFieldDefinitions('node','advisory_committee_content');
	/*foreach($contentFields as $fname=>$fi){
		$fname = 'advf_'.strtolower($fname);
		if(isset($bundle_fields[$fname]) ){
		 print $fname." : targetEntityTypeId : ".$bundle_fields[$fname]->getTargetEntityTypeId()."\n";
		}
	}*/
	$properties = array( 'targetEntityType'=>'node', 'bundle'=>'advisory_committee_content');
	$form_displays = \Drupal::entityManager()->getStorage('entity_form_display')->loadByProperties($properties);
	if(!$form_displays){
		print "Error: Could not find any form displays targetting at folio list items\n"; return;
	}
	foreach($form_displays as $form_display){
		//$fcomp = $form_display->getComponent('field_folio_reference');
		//print "folio ref: ".print_r($fcomp,true)."\n"; break;
		foreach($contentFields as $fname=>$fi){
			$fname = 'advf_'.strtolower($fname);
			if($component = $form_display->getComponent($fname)){
				print $fname." : has component type ".$component['type']."\n";
			} else {
				print $fname. ".. no component\n";
				$form_display->setComponent($fname, array(
					'weight'=>30,
					'type'=>'inline_entity_form_complex',
					'settings' => array(
						'label_singular' => null,
            					'label_plural' => null,
            					'allow_new' => 1,
            					'allow_existing' => 1,
            					'match_operator' => 'CONTAINS',
            					'override_labels' => null
					)
				) )->save();
			}
		}
	}
	print "Finished displays ".count($form_displays)." \n";
}


/** output list of fields attached to given bundle/content entity **/
function displayBundleFields($bundle_name){
	$bundle_fields = \Drupal::entityManager()->getFieldDefinitions('node',$bundle_name);
	print $bundle_name." Fields:\n";
	foreach($bundle_fields as $fname=>$field_config){
		//if($fname!='field_folio_reference') continue;
		if("advf_"!=substr($fname,0,5)){ continue; }
		print $fname." : ".print_r($field_config,true)."\n";
		//continue;
		/*$field_config->setSetting('handler_settings', array(
			'target_bundles'=>array('advisory_folio_list_item'=>'advisory_folio_list_item')
			)
		);
		$field_config->save();
		*/
		//$settings = $field_config->getSettings();
		//print "settings : ".print_r($settings,true)."\n";
		$storage = $field_config->getFieldStorageDefinition();
		//$storage->setCardinality(50);
		//$storage->save();
		print "Storage : ".print_r($storage,true)."\n";
	}
}


/** go through the results from loadfile and parse each folio file for folio entries and field info **/
function parseFolioXML($loader_root,$loadfile_results){
	$results = array( 'content_fields'=>array(),'fields'=>array('nodeid'=>'string'), 'entries'=>array() );
	foreach($loadfile_results as $record){
		$title = isset($record['atr']['dDocTitle']) ? $record['atr']['dDocTitle'] : '';
		foreach($record['val'] as $i=>$field){
			if( $field['atr']['name']!=='primaryFile:path'||substr($field['val'],-4)!="xcsr" ){ continue; }
			if(!file_exists($loader_root.$field['val'])){
				print "Invalid file: ".$loader_root.$field['val']."\n";
				continue;
			}
			//print "opening $loader_root".$field['val']."\n";			
			$xml = new XMLReader();
			$xml->open($loader_root.$field['val']);
            		$field_obj = xml2assoc($xml, "root");
            		$xml->close(); 
            		print "Read file ".$field['val']."\n";
            		$docname = substr( substr($field['val'],8),0,-7);
			foreach($field_obj[0]['val'][1]['val'][0]['val'][1]['val'] as $xfieldnode){
				$folioID = $xfieldnode['atr']['nodeId'];
				$props = array('nodeid'=>$folioID);
				foreach($xfieldnode['val'][0]['val'] as $xprop){
            				$xname = substr($xprop['atr']['key'],5);
					$props[$xname] = $xprop['atr']['value'];
				}
				if(!isset($props['name']) || strlen($props['name'])<1) { 
					print "Invalid folio name\n"; continue; 
				}
				if(!$xfieldnode['val'][1]['val']){
					//print "No children\n"; 
					continue;
				}
				if(!isset($results['content_fields'][$props['name']]) ){
					$results['content_fields'][$props['name']] = 1;
				}
				$props['docname'] = $docname;
				print $props['name']." has ".count($xfieldnode['val'][1]['val'])." children\n";
				$list_items = array();
				foreach($xfieldnode['val'][1]['val'] as $xprop){
					$slotID = $xprop['atr']['nodeId'];
					$slot = array('nodeid'=>$slotID,'folioid'=>$folioID,'folio_name'=>$props['name']);
					foreach($xprop['val'][0]['val'] as $sprop){
						$propname = strtolower(substr($sprop['atr']['key'],5));
						$slot[$propname] = $sprop['atr']['value'];
						if( isset($slot['fslot_createdate']) ){
							preg_match('/(\d\d\d\d\-\d\d\-\d\d)/',$slot['createdate'],$matches);
                                        		if($matches && count($matches)>1 ) {
                                                		$slot['createdate'] = $matches[1];
                                        		}
						}
						if($propname=='ddoctitle'){ $slot['title'] = $sprop['atr']['value']; }
						if(!isset($results['fields'][$propname])){
							$results['fields'][$propname] = 'string';
						}
					}
					array_push($list_items,$slot);
				}
				$entry = array( 'props'=>$props, 'list_items'=>$list_items);
				array_push($results['entries'],$entry);
				
			}

		}
	}
	//print "All results.. \n".print_r($results,true)."\n";
	return $results;
}


/** parse loadfile.xml to find all the content entries **/
function parseLoadFile($loadfile_path){
	print "Loading loadfile..\n";
	$xml = new XMLReader();
	$xml->open($loadfile_path);
	$loadfile_obj = xml2assoc($xml, "root");
	$xml->close();

	//actual results array is nested several children deep
	$resultset = $loadfile_obj[0]['val'][0]['val'][0]['val'][0]['val'][0]['val'];
	return $resultset;
}



/** find all folio fields connected to advisory content and delete them **/
function clearAdvisoryFields(){
	$bundle_fields = \Drupal::entityManager()->getFieldDefinitions('node','advisory_committee_content');
	foreach($bundle_fields as $fname=>$field_config){
		if("advf_"!=substr($fname,0,5) ) continue;
		//print "Found field ".$fname."\n"; continue;
		try {
			removeField($fname,'advisory_committee_content');
		} catch (Exception $e){
			print "Exception when trying to remove $fname: ".$e."\n";
		}
	}
}

function clearAllFields($node_type){
        $allfields = \Drupal::entityManager()->getFieldDefinitions('node',$node_type);
        print "Found ".count($allfields)." fields to remove\n";
	foreach($allfields as  $fname=>$field_config){
		if("field"!=substr($fname,0,5))continue;
		removeField($fname, $node_type);
	}

}



function removeField($field_name,$bundle_name){
	$field_config = Drupal::entityManager()->getStorage('field_config')->loadByProperties( array('field_name'=>$field_name) );
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
	if(isset($field_config['node.'.$bundle_name.'.'.$field_name])){
		try {
		$field_config['node.'.$bundle_name.'.'.$field_name]->delete(); 
		} catch (Exception $e){
		 print "Error deleting field_config for ".$field_name.": ".$e->getMessage()."\n";
		}
	
	}
	if($field_storage) {
		try {
			 $field_storage->delete(); 
		} catch (Exception $e){
                 print "Error deleting storage for ".$field_name.": ".$e->getMessage()."\n";
                }
}
	print "Deleted $field_name \n";
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


/** add a field with a basic string/text type to a bundle **/
function addField($field_name, $bundle_name, $field_type, $field_label,$add_display){
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
	if($field_storage){
		print "Field storage: ".$field_name." already exists. Using existing\n";
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
    		'label' => $field_label,
  	));
  	$field->save();
	print "Saved field: "+$field_name+"\n";

	if(!$add_display){ return; }//skip adding the display, like if it uses something tricky	

	entity_get_form_display('node', $bundle_name, 'default')
		->setComponent( $field_name, array(
			'type' => 'text_textarea',
			'weight' => 1,
			'format' => ($field_type!='string')?'full_html':null,
			'settings' => array('text_processing'=>0)
		))
		->save();
	$display_info = entity_get_form_display('node', $bundle_name, 'default')->getComponent($field_name);
}


/*** add an entity_reference field that uses inline_entity_form for display **/
function addEntityReference($field_name, $bundle_name, $field_label, $target_entity){
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
        if($field_storage){
                print "Field storage: ".$field_name." already exists. Using existing\n";
        } else {
        $field_storage = entity_create('field_storage_config', array(
                'field_name' => $field_name,
                'entity_type' => 'node',
                'type' => 'entity_reference'
        ));
        }
        $field_storage->save();

        $field = entity_create('field_config', array(
                'entity_type' => 'node',
                'field_name' => $field_name,
                'field_storage' => $field_storage,
                'bundle' => $bundle_name,
                'type' => 'entity_reference',
                'label' => $field_label,
        ));
	$field->setSetting('target_type',$target_entity);
	$field->setSetting('handler','default');
	$field->setSetting('handler_settings', array(
                        'target_bundles'=>array($target_entity=>$target_entity)
                        )
                );

        $field->save();
	print $field_name." entity_reference to ".$target_entity." saved\n";

}



/*** Check whether field exists (has a field_storage) **/
function checkFieldExists($field_name){
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
	return ($field_storage) ? true : false;
}

/*** Check if the field exists and is attached to the bundle **/
function checkFieldExistsBundle($field_name,$bundle_name, $attach_it=false){
	$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node',$field_name);
	if(!$field_storage) return false;
	$field_config = \Drupal::entityManager()->getStorage('field_config')->loadByProperties( array('field_name'=>$field_name) );
	if(!$field_config||!isset($field_config['node.'.$bundle_name.'.'.$field_name]) ){
		if(!$attach_it) return false;
		$field = entity_create('field_config', array(
                'entity_type' => 'node',
                'field_name' => $field_name,
                'field_storage' => $field_storage,
                'bundle' => $bundle_name,
                'type' => $field_storage->getEntityTypeId(),
                'label' => $field_name,
        		));
        	$field->save();
		print "Attached field ".$field_name." to bundle ".$bundle_name."\n";
		return true;
	}
	return true;
}


/**** load a certain type of entity by id or by  field ****/
function loadRecordByField($content_type,$record_field,$record_value){
	$nids = Drupal::entityQuery('node')->condition('type',$content_type)
		->condition($record_field,$record_value)
		->execute();
	if($nids && count($nids)>0){
		$nidkeys = array_keys($nids);
		return \Drupal::entityManager()->getStorage('node')->load($nids[$nidkeys[0]]);
	} else {
		return null;
	}
}

function loadRecordById($recordId){
	return \Drupal::entityManager()->getStorage('node')->load($recordId);
}


function loadRecordsByContentType($content_type){
  	$nids = Drupal::entityQuery('node')->condition('type',$content_type)
                ->execute();
        if($nids && count($nids)>0){
                $nidkeys = array_keys($nids);
                return \Drupal::entityManager()->getStorage('node')->loadMultiple($nidkeys);
        } else {
                return null;
        }

}
