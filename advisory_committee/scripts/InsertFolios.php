#!/usr/bin/env drush
<?php
/**
parse xml files, load into Drupal
**/

$drupal_root = "/var/www/html/fda/";
$loader_root = "/var/www/html/fda/advisory_committee/";

$entity_manager = \Drupal::entityManager();
$bundles = $entity_manager->getBundleInfo('node');

if( !isset( $bundles['advisory_folio_node'] ) ){
        print "Folio content type missing\n"; //exit;
}

require_once($loader_root.'scripts/Utils.php');

//viewRecordField(112, 'field_folio_reference'); exit;
//displayBundleFields('advisory_committee_content'); print"\n"; exit;


//clearAdvisoryFields();

$loadfile_path = $loader_root."LOADFILE.xml";
$loadfile_results = parseLoadFile($loadfile_path);

$folioData = parseFolioXML($loader_root,$loadfile_results);

$folioData['node_fields'] = array(
	'field_folion_nodeid'=>'string',
	'field_folion_ddocname'=>'string',
	'field_folion_label'=>'text',
	'field_folion_name'=>'string',
	'field_folion_list'=>array('type'=>'entity_reference','target'=>'advisory_folio_list_item')
);

if( !isset($folioData['fields']['link']) ){ $folioData['fields']['link'] = 'link';  } //link will be the default$
if( !isset($folioData['fields']['file']) ){ $folioData['fields']['file'] = 'file';  }
if( !isset($folioData['fields']['image']) ){ $folioData['fields']['image'] = 'image';  }
if( !isset($folioData['fields']['text']) ){ $folioData['fields']['text'] = 'text_long';  }
//print "Add content fields:\n".print_r($folioData['content_fields'],true)."\n";
//print "Add folio fields:\n".print_r($folioData['entries'],true)."\n";

//addAllFolioFields($folioData);
addFolioEntries($folioData['entries']);

//addContentFields($folioData['content_fields']);
//addInlineFormDisplays($folioData['content_fields']);
//addFolioFields($folioData['fields']);
//addFolioItems($folioData['entries']);

print "\nDone with tests\n";


//go through all folio nodes and add the node and connected list items and connect to main content item
function addFolioEntries($entries){
	print "Adding ".count($entries)." folio nodes..\n";
	foreach($entries as $i=>$fnode){
		print $fnode['props']['docname'].' - '.$fnode['props']['name']."\n";
		$rnode = array(
                                'nid'=>null,
                                'type'=>'advisory_folio_node',
                                'status'=>true,
                                'body'=>'',
                                'title'=>$fnode['props']['name']
                        );
		foreach($fnode['props'] as $propname=>$propvalue){
			switch($propname){
				case 'name':
					$rnode['field_folion_name'] = $propvalue;
					break;
				case 'docname':
					$rnode['field_folion_ddocname'] = $propvalue;
					break;
				case 'label':
					$rnode['field_folion_label'] = $propvalue;
					$rnode['body'] = $propvalue;
					break;
				case 'nodeid':
					$rnode['field_folion_nodeid'] = $propvalue;
					break;
			}
		}
		if(!$rnode['field_folion_ddocname']){
			print "Missing ddocname for folio node\n"; continue;
		}

		$cnode = loadRecordByField('advisory_committee_content','field_ddocname', $rnode['field_folion_ddocname']);
		if(!$cnode){
			print "Could not find content with field_ddocname = ".$rnode['field_folion_ddocname']."\n";
			continue;
		}
		$cnode_ref = $cnode->get('field_calendar_folio_ref')->getValue();
		print "Previous folio refs: ".print_r($cnode_ref,true)."\n";

		$rnode_prev = loadRecordByField('advisory_folio_node','field_folion_nodeid', $rnode['field_folion_nodeid']);
		if($rnode_prev){
			print "Found existing record with this ddocname\n";
		} else {
			//save record
			$rnode_prev = Drupal::entityManager()->getStorage('node')->create($rnode);
                        $rnode_prev->save();
			print "Saved folio node!\n";
		}
		$rnode_id = $rnode_prev->id(); 
		$cnode_ref = addIdToArray($cnode_ref, $rnode_id);
		print "New folio ref: ".print_r($cnode_ref,true)."\n";
		$cnode->set('field_calendar_folio_ref',$cnode_ref);
		$cnode->save();

		//now handle folio list items
		foreach($fnode['list_items'] as $i=>$fitem){
			$lnode = array(
                                'nid'=>null,
                                'type'=>'advisory_folio_list_item',
                                'status'=>true,
                                'body'=>'',
                                'title'=>'Folio List Item'
                        );
			foreach($fitem as $propname=>$propvalue){
                        	switch($propname){
                                	case 'title':
                                        	$lnode['title'] = $propvalue;
                                        	break;
        	                	}
				$lnode['field_folio_'.$propname] = $propvalue;
	                }
			
			$lnode['field_folio_link'] = array(
                                        'uri' => '',
                                        'title' => $lnode['title'],
                                        'options' => array()
                                );
                        if( $lnode['field_folio_dformat']=='text/html'
                        ||$lnode['field_folio_dformat']=='Application/xml'
                        ||$lnode['field_folio_dformat']=='idcmeta/html'
                        ||(!isset($lnode['field_folio_dformat'])&&$lnode['field_folio_xlinkwebaddress'])
                        ){
                                $lnode['field_folio_link'] = array(
                                        'uri' => $lnode['field_folio_xlinkwebaddress'],
                                        'title' => $lnode['field_folio_ddoctitle'],
                                        'options' => array()
                                );
                        } else if(!isset($lnode['field_folio_dformat']) && $lnode['field_folio_docurl']){
                                $lnode['field_folio_link'] = array(
                                        'uri' => $lnode['field_folio_docurl'],
                                        'title' => $lnode['field_folio_ddoctitle'],
                                        'options' => array()
                                );

                        } else if($lnode['field_folio_downloadurl']) {
                                $lnode['field_folio_link']['uri'] = $lnode['field_folio_downloadurl'];
                        } else {
                                print "CANT FIND LINK\n";  print_r($lnode); print"\n"; continue;
                        }

			$lnode_prev = loadRecordByField('advisory_folio_list_item','field_folio_nodeid', $rnode['field_folio_nodeid']);
	                if($lnode_prev){
        	                print "Found existing record with this nodeid\n";
                	} else {
                        	//save record
                        	$lnode_prev = Drupal::entityManager()->getStorage('node')->create($lnode);
                        	$lnode_prev->save();
                        	print "Saved folio list item!\n";
                	}
			$lnode_id = $lnode_prev->id();
			$rnode_ref = $rnode_prev->get('field_folion_list')->getValue();
			$rnode_ref = addIdToArray($rnode_ref, $lnode_id);
			print "rnode_ref: ".print_r($rnode_ref,true)."\n";
			$rnode_prev->set('field_folion_list', $rnode_ref);
			$rnode_prev->save();

		}
	


		
	}

}


//add fields if they aren't set to folio nodes and list items
function addAllFolioFields($folioData){
	foreach($folioData['node_fields'] as $fname=>$ftype){
		if($fname=='')continue;
		if(!checkFieldExistsBundle($fname,'advisory_folio_node',true)){
			print "Create field ".$fname." - ".$ftype."\n";
                        createFieldBundle('advisory_folio_node',$fname,$ftype);
                }
	}

	foreach($folioData['fields'] as $fname=>$ftype){
		if($fname=='')continue;
		$fname = 'field_folio_'.$fname;
                if(!checkFieldExistsBundle($fname,'advisory_folio_list_item')){
			print "Create field ".$fname." - ".$ftype."\n";
                        createFieldBundle('advisory_folio_list_item',$fname,$ftype);
                }
        }

}


function createFieldBundle($bundle, $fname, $ftype){
        print $bundle." - ".$fname." - ".print_r($ftype,true)." .. \n";
        if(is_array($ftype)){
                if( !isset($ftype['type'])|| $ftype['type']!='entity_reference'){
                        print "Invalid field type\n"; return;
                }
                $target = $ftype['target'];
                addEntityReference( $fname, $bundle, $fname, $target );
        } else {
                addField($fname, $bundle, $ftype, $fname, false);
        }
}

