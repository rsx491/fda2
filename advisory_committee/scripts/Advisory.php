#!/usr/bin/env drush
<?php
/**
parse xml files, load into Drupal
**/


$drupal_root = "/var/www/html/fda/";
$loader_root = "/var/www/html/fda/advisory_committee/";
require_once($loader_root.'scripts/Utils.php');




$loadfile_results = parseLoadFile($loader_root.'LOADFILE.xml');
$parsed = parseContentXML($loader_root, $loadfile_results);
//print "Parsed records from xml:\n".print_r($parsed['loadfile_fields'],true)." \n";
$preset_fields = updateContentFields($parsed);
addContentRecords($parsed, $preset_fields);






/*
function checkModulesInstalled($drupal_root, $loader_root){
	$entity_manager = \Drupal::entityManager();
	$bundles = $entity_manager->getBundleInfo('node');

print "Checking of modules are activated..\n";
//check if module for advisory content type is already in drupal directory
//if( file_exists($drupal_root."modules/advisory_committee/advisory_create_content_type") ){

if(0&& isset( $bundles['advisory_committee_content'] ) ){
        print "Advisory Committee content type module already exists, skipping creation\n";
} else {
        print "Copying advisory committee content type module and activating it..\n";
        $cmd = shell_exec("cp -R ".$loader_root."modules/advisory_committee ".$drupal_root."/modules/.");
        $cmd = shell_exec("drush en advisory_create_content_type");
        print "done\n";
}

if( 1||isset( $bundles['advisory_folio_list_item'] ) ){
        print "Advisory Committee Folio module already exists, skipping creation\n";
} else {
        print "Copying advisory committee folio module and activating it..\n";
        //$cmd = shell_exec("cp -R ".$loader_root."advisory_committee/advisory_committee_folio ".$drup$
        $cmd = shell_exec("drush en advisory_folio_item");
        print "done\n";
}

}
*/

function addContentRecords($parsed, $preset_fields){
	print "Looping through ".count($parsed['records'])." records to add..\n";
	foreach($parsed['records'] as $record){
		$content = array(
                                'nid'=>null,
                                'type'=>'advisory_committee_content',
                                'status'=>true,
                                'body'=>'',
                                'title'=>'advisory committee calendar entry'
                        );
		$meta = array(
                                'nid'=>null,
                                'type'=>'advisory_committee_content_meta',
                                'status'=>true,
                                'body'=>'',
                                'title'=>'advisory committee metadata'
                        );
		if(isset($record['adv_ddoctitle']) ){
			$content['title'] = $record['adv_ddoctitle'];
		}
		if(isset($record['adv_ddocname']) ){
			$meta['title'] = 'Advisory Committee Metadata for '.$record['adv_ddocname'];
		}
		if(isset($record['xEditionDate')){
			$meta['field_meta_edition_date'] = 'Advisory Committee Metadata for '.$record['xEditionDate'];
		}
		if(isset($record['field_advisory_maincontent']) ){
			$mc = $record['field_advisory_maincontent'];
			preg_match('/summary="meeting date, time, location and sponsoring Center"/',$mc,$matches);
			if(0 && $matches && count($matches) > 0){
				print "Found meeting table, parsing out relevant information..\n";
				//print $mc."\n\n"; 
				$pat = '/Location<\/th><\/tr><tr.+?><td>(.+?)<\/td><td>(.+?)<\/td><td>(.+?)<\/td><td>(.+?)<\/td>/mi';
				preg_match($pat,$mc,$matches);
				if(count($matches)>4){
					$content['field_calendar_location'] = $matches[4];
					$content['field_calendar_center'] = array('value'=>'CBER');
					//try to convert date range
					$pat = '/^(\w+?)\s(\d\d)\-(\d\d),\s(\d\d\d\d)/';
					$datestr = $matches[2];
					preg_match($pat, $datestr, $matches);
					if( count($matches) > 1){
						$content['field_calendar_event_date_one'] = DateTime::createFromFormat('M d Y',$matches[1].' '.$matches[2].' '.$matches[4] );
						$content['field_calendar_event_date_two'] = DateTime::createFromFormat('M d Y',$matches[1].' '.$matches[3].' '.$matches[4] );
					} else {
						print "Couldnt match datestr: ".$datestr."\n";
					}
				} else {
					print "Didn't match table headers\n";
				}
				$pat = '/<table border="1" cellspacing="0" summary="meeting date, time, location and sponsoring Center.+?<\/table>/';
				$mc = preg_replace($pat,'',$mc);
				$content['field_calendar_main_content'] = $mc;
			} else {
				
			}
		} else {
			 
		}

		foreach($record as $fname=>$fvalue){
			if(isset($preset_fields['xml'][$fname]) ){
				$content[ $preset_fields['xml'][$fname] ] = $fvalue;
			} else {
				$meta[$fname] = $fvalue;
			}
		}

		$meta_prev = loadRecordByField('advisory_committee_content_meta','title', $meta['title']);
		if(!$meta_prev){
			//save meta record
			$meta_prev = Drupal::entityManager()->getStorage('node')->create($meta);
			$meta_prev->save();
		}
		print "Meta saved to record ".$meta_prev->id()."\n";
		$meta_array = array( array('target_id'=>$meta_prev->id()  ) );

		$content_prev = loadRecordByField('advisory_committee_content','field_ddocname', $content['field_ddocname']);
		if($content_prev){
			//content node already exists, dont overwrite just make meta connected
			$content_prev->set('field_calendar_meta', $meta_array );
			$content_prev->save();
			print "Document ".$content_prev->id()." already exists, continuing\n";
			print_r($content_prev->get('field_calendar_meta')->value,true); print "\n";
			continue;
		}
		$content_prev = Drupal::entityManager()->getStorage('node')->create($content); 
		$content_prev->save();
		$content_prev->set('field_calendar_meta',array( array('target_id'=>$meta_prev->id())  ) );
                $content_prev->save();
		print "Saved [".$content_prev->get('field_ddocname')."]\n";
		

		//print "Converted content/meta records.. ".print_r($content,true)."\n"; exit;

	}

}


function updateContentFields($results){
	$preset_fields = array(
		'nonxml' => array(
			'field_calendar_center' => 'list_string',
		//	'field_calendar_event_date_two' => 'datetime',
		//	'field_calendar_event_date_one' => 'datetime',
			'field_calendar_location' => 'text_long',
			'field_calendar_main_content' => 'text_long',
		//	'field_calendar_time_one' => 'string',
		//	'field_calendar_time_two' => 'string',
			'field_calendar_update' => 'text_long',
			'field_category' =>  array('type'=>'entity_reference','target'=>NULL),
			'field_calendar_folio_ref' => array('type'=>'entity_reference','target'=>'advisory_folio_node'),
			'field_delete'=>array('type'=>'entity_reference','target'=>null),
			'field_calendar_meta'=>array('type'=>'entity_reference','target'=>'advisory_committee_content_meta'),
		),
		'xml'=>array(
			'adv_ddocname' => 'field_ddocname',
			'adv_ddocauthor' => 'field_ddocauthor',
			'adv_dreleasedate' => 'field_dreleasedate',
			'adv_xlanguage' => 'field_xlanguage',
			'adv_xnextreview' => 'field_xnextreview'
		)
	);
	print "Looping through fields and attaching them to content type or meta..\n";
	foreach($preset_fields['nonxml'] as $fname=>$ftype){
		if(!checkFieldExistsBundle($fname,'advisory_committee_content',true)){
			createFieldBundle('advisory_committee_content',$fname,$ftype);
		}
	}
	//print "Done checking nonxml\n"; return;
	foreach($results['loadfile_fields'] as $fname=>$ftype){
		$bundle = 'advisory_committee_content_meta';
		if( isset($preset_fields['xml'][$fname] ) ){
			$fname = $preset_fields['xml'][$fname];
			$bundle = 'advisory_committee_content';
		}
		if(!checkFieldExistsBundle($fname,$bundle)){
                        createFieldBundle($bundle,$fname,$ftype);
                }
	}
	print "Done checking fields..\n";
	return $preset_fields;

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


function parseContentXML($loader_root, $resultset){
	$loadfile_fields = array();$fields_added = array(); $records = array();
foreach($resultset as $record){
	$title = isset($record['atr']['dDocTitle']) ? $record['atr']['dDocTitle'] : '';
	$newRecord = array( 'field_advisory_doctitle' => $title, 'field_advisory_docname'=>$record['atr']['dDocName'] );
	foreach($record['val'] as $i=>$field){
		if( $field['atr']['name']==='primaryFile:path' ){
			//continue; //skip loading xml files are already loaded
			$is_xcsr = (substr($field['val'],-4)=="xcsr") ? true : false;
			if($is_xcsr) continue;
			$xml = new XMLReader();
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
					$loadfile_fields[$field_name] = 'text_long';
				}
			}
		}  else if( isset($field['atr']['name']) ) {
			//continue;
			$field_name = 'adv_'.strtolower($field['atr']['name']);
			if( strlen($field_name) > 32) {
				$field_name = substr($field_name, 0,31);
			}
			$field_type = ($field_name=='adv_xdescription'||$field_name=='adv_xkeyword')?'text_long':'string';
			$loadfile_fields[$field_name]=$field_type; 

			$newRecord[ $field_name ] = $field['val'];
		} else {
			print "x";
		}
	}
	array_push($records, $newRecord);
	//break;
  } //end foreach

  return array('fields_added'=>$field_added,'records'=>$records,'loadfile_fields'=>$loadfile_fields);

}
