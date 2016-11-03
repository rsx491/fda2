#!/usr/bin/env drush
<?php
/**
Go through advisory content nodes and parse maincontent for calendar dates
**/

$drupal_root = "/var/www/html/fda/";
$loader_root = "/var/www/html/fda/advisory_committee/";

require_once('/var/www/html/fda/advisory_committee/scripts/Utils.php');

/*
if(!checkFieldExistsBundle('advisory_committee_content','field_advisory_event_group_date')){
	print "Adding field_advisory_event_group_date date field\n";
	addField('field_advisory_event_group_date','advisory_committee_content','datetime','Advisory Committees Event Group Date',true);
}
*/

//$single_docname = 'UCM223189';

$content_nodes = loadRecordsByContentType('advisory_committee_content');
print "Found ".count($content_nodes)." content nodes\n";

$loadfile_results = parseLoadFile($loader_root.'LOADFILE.xml');

$failed_records = array();
$recordUpdated = false;

//foreach($content_nodes as $content){
foreach($loadfile_results as $record_i=>$record){
	$recordUpdated = true;
	$content = null;
	$xEditionDate=null;
foreach($record['val'] as $i=>$field){
	if(isset($field['atr']['name']) && $field['atr']['name']=='xEditionDate'){
		$xEditionDate=$field['val'];
	}
	if(!isset($field['atr']['name']) || $field['atr']['name']!='primaryFile:path' ) continue;
	if((substr($field['val'],-4)=="xcsr")) continue;
	$doctitle = $record['atr']['dDocTitle'];
	$docname = substr(  substr($field['val'],8),0,-4);
	if($single_docname&&$docname!=$single_docname) continue;
	print $doctitle." : ".$docname."\n"; 
	$xml = new XMLReader();
        $xml->open($loader_root.$field['val']);
        $field_obj = xml2assoc($xml, "root");
        $xml->close();

	$mc_orig = null;
	foreach($field_obj[0]['val'] as $xi=>$xfield){
		if( 'wcm:' != substr($xfield['name'],0,4) ) continue;
		if( 'MainContent' != $xfield['atr']['name'] ) continue;
		//print "Found mc:\n".$xfield['val']."\n";
		$mc_orig = $xfield['val'];
		break;
	}
	$recordUpdated = false;
	if(!$mc_orig){ print "Could not find maincontent for this doc\n"; continue; };

	//get matching content node
	$content = loadRecordByField('advisory_committee_content','field_ddocname',$docname);
	if(!$content){
		print "Could not find existing document record!\n"; continue;
	}


	$updateContentMC = false;
	if(strlen($content->field_calendar_main_content->value)!=strlen($mc_orig)){
		print "Meta maincontent has different strlen than content maincontent!\n";
		$updateContentMC = true; 
	}
	$mc = html_entity_decode($mc_orig);
        $mc = preg_replace('/<!\-\-\s*Instance.+?\-\->/','',$mc);

	//just update main content with decoded field and continue, skip parsing for calendar details
	$content->field_calendar_main_content->value = $mc;
        $content->save();
	$recordUpdated=true; continue;


	$mc_no_br = preg_replace('/<br\s*(\/)*>/','',$mc);
	//print substr($mc,0,400)."\n";
	$format='table';//calendar info can be in table or set of spans or just <p>..because sure why not make this more complicated to parse 
        preg_match('/summary="(meeting date, time, location and sponsoring Center|This table shows the date, time)/',$mc,$matches);
        if(!$matches || count($matches) < 1){
		preg_match('/^<p><strong>Date\:/', $mc, $matches);
		if(!$matches || count($matches)<1){
			
			//test for vertical table bullshit
			if(preg_match('/The meeting (?:will be|was) held on the following date(?:s*)/', $mc, $matches) ){
				$format = 'table-vertical'; print "Vertical Table\n";
			} else if( preg_match('/<strong>Date and Time</',$mc,$matches) ){
				$format = 'table-date';
			} else if(preg_match('/Center<\/.+?Date<\/th/msi',$mc,$matches) && preg_match('/Location<\/th><\/tr><tr/',$mc, $trash) ){
				$format = 'table-center-date';
			} else if( preg_match('/<table.+?Location</msi', $mc, $trash) || preg_match('/The meeting will be(?:<br \/>)*\s*held on/',$mc,$trash) ) {

			} else if( preg_match('/<strong>Date\:*</',$mc,$trash) ){
			
			} else if(preg_match('/is postponing.+?the.+?meeting/',$mc,$trash) ){
			  print "Meeting is postponed, do not save data\n"; $recordUpdated=true; break;
			} else {
				array_push($failed_records,array('docname'=>$docname,'reason'=>'did not find meeting table'));
				print "Did not find meeting table in maincontent..\n".substr($mc,0,700)."\n"; break;
			}
		} else {
			if( !preg_match('/^<p><strong>Date:<\/strong>.+?Time:<\/strong>.+?<\/p>/mi',$mc,$matches) ) {
				$format = 'spanp';
				print "Date calendar is in broken P format..\n";
			} else {
		 		print "Date calendar is in span format.. \n";
				$format = 'span';
			}
		}
		
	} else if(preg_match('/<tr><th.+?>Center/', $mc, $throwaway) ){
		$format = 'table2'; //yet a different table layout..
	}
	$pat = '/<tr.*?>\n*\s*<td.*?>(.+?)<\/td>\n*\s*<td.*?>(.+?)<\/td>\n*\s*<td.*?>(.+?)<\/td>\n*\s*<td.*?>(.+?)<\/td>/msi';
	if($format=='span'){
		$pat = '/^<p><strong>(Date):<\/strong>(.+?)<.+?<\/strong>(.+?)<.+?<\/strong>(.+?)<\/p>/mi';
		//print "Span calendar:\n".$mc."\n";
	} else if($format=='spanp'){
		$pat = '/^<p><strong>(Date):<\/strong>(.+?)<[.\n]*?<strong>Time:<\/strong>(.+?)<\/p>\n*<p><strong>Location:<\/strong>(.+?)</msi';
		$pat = '/^<p><strong>(Date):<\/strong>[^A-Z]*?([A-Z].+?)<.+?Time:\s*<\/strong>.*?(\d+:.+?)<.+?Location:<\/strong>[^A-Z]*?(.+?)<\/p/msi';
	} else if($format=='table2'){
		$pat = '/<tr>\s*<td.+?>(.+?)<\/td>\s*<td.+?>(.+?)<\/td>\s*<td.+?>(.+?)<\/td>\s*<td.+?>(.+?)<\/td/mi';
	} else if($format=='table-vertical'){
		$pat = '/<strong>(Date and Time)<\/strong>.+?following date:<br.+?>(\w+\s\d+,\s\d+),\s(\d.+?)<\/td.+?Location.?top">(.+?)<\/td>/msi';
		$pat = '/(Date and Time)<\/.+?following date(?:s*):<b.+?>(\w+ \d+, \d+),*\s*(.+?)<\/td.+?Location<\/.+?(?:top"|<td)>(.+?)<\/td/msi'; 
	} else if($format=='table-date'){
		if( preg_match('/(The meeting will be held on)\s(\w.+?\d\d\d\d),\sfrom\s(\d+.+?to.+?m\.).+?Location.+?(<p>.+?)<\/td>/msi', $mc, $trash) ){
			$pat = '/(The meeting will be held on)\s(\w.+?\d\d\d\d),\sfrom\s(\d+.+?to.+?m\.).+?Location.+?(<p>.+?)<\/td>/msi';
			$format='table-date-2';
		} else {
			$pat = '/<strong>(Date and Time)<\/.+?top">(.+?\d\d\d\d),*\s+(\d.+?)<.+?Location<.+?top">(.+?)<\/td/msi';
		}
	} else if($format=='table-center-date' && preg_match('/Location<\/th><\/tr><tr/',$mc, $trash) ){
		$pat = '/>Location<\/th.+?<td.+?>(\w+?)<\/td>\s*<td.+?>(.+?)<.+?<td.+?>(.+?)<.+?<td.+?>(.+?)<\/td/msi';
		$format="table location1";
	} else if( preg_match('/<tr.+?><th>Center<\/th.+?Location<\/th><\/tr><tr.*?>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>/msi',$mc,$matches) ){
                $pat = '/Location<\/th><\/tr><tr.*?>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>/msi';
        	$format="table location2";
	} else if( preg_match('/(Location)<\/th><\/tr><tr.*?>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>/msi',$mc,$matches) ){
		$pat = '/(Location)<\/th><\/tr><tr.*?>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>/msi';
		$format="table location3";
	} else if( preg_match('/Location<\/th.+?<p>(\w+?)(?:\&nbsp;)+(\w+\s\d+,\s\d+)(?:\&nbsp;)+(\d+\:.+?\.m\.)\&nbsp;(.+?)<\/p>/msi',$mc,$matches) ){
		$pat = '/Location<\/th.+?<p>(\w+?)(?:\&nbsp;)+(\w+\s\d+,\s\d+)(?:\&nbsp;)+(\d+\:.+?\.m\.)\&nbsp;(.+?)<\/p>/msi';
		$format = 'too many nbsp';
	} else if( preg_match('/Location<\/th><\/tr><\/tbody><\/table><p>.+?<\/p>/msi',$mc,$matches) ){
                $pat = '/Location<\/th><\/tr><\/tbody><\/table><p>(\w+).+?(\w+\s\d+,\s\d+).+?(\d.+?\-.+?m\.).+?(\w.+?)<\/p>/msi';
                $format = 'too many spaces';
        } else if(preg_match('/The meeting will be held on .+?<\/td>/', $mc, $trash) ){
		$format = 'vertical tds';
		$pat = '/(The meeting will be held on)\s(\w.+?\d\d\d\d),\sfrom\s(\d+.+?to.+?m\.).+?Location.+?(<p>.+?)<\/td>/msi';
	} else if(preg_match('/Location<\/.+?<tr.*?>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>/msi',$mc,$trash) ){
		$format = 'Location tr';
		$pat = '/Location<\/.+?<tr.*?>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>\s*<td.*?>(.+?)<\/td>/msi';
	}else if(preg_match('/<b>The meeting will.+?([A-Z].+?\d\d\d\d).+?(\d\s.+?)<\/(?:div|p).+?Location:.+([A-Z].+?)<\/(?:div|p)/ms',$mc,$trash) ){
		$format = 'all paragraphs bold';
		$pat = '/<b>(The meeting) will.+?([A-Z].+?\d\d\d\d).+?(\d.+?)<\/(?:div|p).+?Location:.+?([A-Z].+?)<(?:div|p|b)[^>]*>Contact/ms';
	}else if(preg_match('/(The meeting will be(?:<br \/>)*\s*held on)\s(\w.+?\d\d\d\d),\s(?:from\s*)*.*?(\d+\:*\d*\s[ap].+?)<.+?Roman\'">Location/msi',$mc,$trash) ){
		$format = 'font insanity';
		$pat = '/(The meeting will be(?:<br \/>)*\s*held on)\s(\w.+?\d\d\d\d),\s(?:from\s*)*.*?(\d+\:*\d*\s[ap].+?)<.+?Location.+?">(.+?)Contact/msi';
	}else if(preg_match('/(The meeting will be(?:<br \/>)*\s*held on)\s(\w.+?\d\d\d\d),\s(?:from\s)*(\d.+?)<.+?Location/msi',$mc,$trash) ){ 
		$format = 'all paragraphs';
		$pat = '/(The meeting will be(?:<br \/>)*\s*held on)\s(\w.+?(?:\d\d\d\d)),\s*(?:<br\s*\/>)*(?:from\s)*\D*(?:<\/span.+?">)*(\d.+?to.+?)<.+?Location.+?([A-Z].+?)<(?:p|div|br\s\/)>\s*\n*Contact/msi';
	} else if(preg_match('/<\/tr.+?<tr(.*?)>\n*\s*<td.*?>(.+?)<\/td>\n*\s*<td.*?>(.+?)<\/td>\n*\s*<td.*?>(.+?)<\/td>\n*\s*<\/tr>/msi',$mc,$trash) ){
		$format = "table 3 column";
		$pat = '/<tr(.*?)>\n*\s*<td.*?>(.+?)<\/td>\n*\s*<td.*?>(.+?)<\/td>\n*\s*<td.*?>(.+?)<\/td>/msi';
	} else if( preg_match('/strong>(Date)\:*<.+?strong>(.+?)<(?:\/p|strong)>.+?Time.+?strong>(.+?)<(?:\/p|strong).+?Location.+?strong>(.+?)<\/p>/msi',$mc,$trash) ){
		$pat = '/strong>(Date)<.+?strong>(.+?\d\d\d\d)\D.+?Time.+?(?:strong>)*(\d.+?)<\/p.+?Location.+?strong>(.+?)<\/p>/msi';
		$format = 'strong paragraphs';
	} else if( preg_match('/strong>(Date)<\/strong>(.+?)<.+?(\d+\:.+?(?:to|\-).+?m\.).+?Location.+?g>(.+?)<\/p/msi',$mc,$trash) ){
		$pat = '/strong>(Date)<\/strong>(.+?)<.+?(\d+\:.+?(?:to|\-).+?m\.).+?Location.+?g>(.+?)<\/p/msi';
		$format = 'strong broken paragraphs';
	} else if(preg_match('/strong>(Date):*<\/strong>(.+?)<.+?\/strong>(.+?)<.+?\/strong>(.+?)</msi',$mc,$trash) ){
		$format = 'strong only';
		$pat = '/strong>(Date):*<\/strong>(.+?)<.+?\/strong>(.+?)<.+?\/strong>(.+?)</msi';
	}
	//$mc_no_br = preg_replace('/<br\s*(\/)*>/','',$mc);
        preg_match($pat,$mc_no_br,$matches); //br's are scattered everywhere, try with them removed first
	if(!$matches || count($matches)<5) preg_match($pat,$mc,$matches);
	//print_r($matches); break;
        if(count($matches)>4){
                                        $tableMatches = $matches;
					//try to convert date range
                                        $pat = '/([A-Z]\w+?)(?:\s|&nbsp;)(\d+)\s*(?:\-|(?:\&amp;)|(?:\&)|(?:and)|(?:\&\#38\;))\s*(\d+),\s(\d\d\d\d)/';
                                        $patSingle = '/([A-Z]\w+?)(?:\s|\&nbsp;)*(\d+?),*(?:\s|&nbsp;)*(\d\d+)/i';
					$patSingle = '/([A-Z][a-z]+).+?(\d+?).+?(\d+)/';
					$patSingleCompat = '/(\d\d)\/(\d\d)\/(\d\d+)/';
					$patDouble = '/([A-Z]\w+?)\s*(\d+),\s(\d\d\d\d)<br.+?>([A-Z]\w+)\s+(\d+),\s+(\d\d\d\d)/';
					$datestr = $matches[2];
					$timestr = preg_replace('/<br\s*(\/)*>/','',$matches[3]);
					$location = $matches[4];
					if($format=='too many nbsp'){
						$location = preg_replace('/(?:\&nbsp;)+/',' ',$location);
					} else if($format=='too many spaces'){
						$location = preg_replace('/\s+/',' ',$location);
					}
					$center = null;
					$d1=null; $d2=null;
					if($format=='table2'||$format=='table-center-date'){
						$center=$matches[1]; 
					} else if(preg_match('/<table.+?meeting\sdate.+?<tr.+?"><th>Center/',$mc,$trash) ) {
						//print "Found center in first spot: ".print_r($matches,true)."\n";
						$center=$matches[1];
					}

                                        preg_match($pat, $datestr, $matches);
                                        if( count($matches) > 1){
                                                $d1 = DateTime::createFromFormat('M d Y',$matches[1].' '.$matches[2].' '.$matches[4] );
                                                $d2 = DateTime::createFromFormat('M d Y',$matches[1].' '.$matches[3].' '.$matches[4] );
						if(!$d1||!$d2){
							array_push($failed_records,array('docname'=>$docname,'reason'=>'invalid double dates: '.print_r($matches,true) ) );

                                                        print "Parsed invalid date double: $datestr : ".print_r($matches,true)."\n"; break;
                                                }

						print "Converted dates (".$datestr."): ".$d1->format('Y-m-d H:i:s')." TO ".$d2->format('Y-m-d H:i:s')."\n";
                                        } else if( preg_match($patDouble, $datestr, $matches) ) {
						$d1 = DateTime::createFromFormat('M d Y',$matches[1].' '.$matches[2].' '.$matches[3] );
                                                $d2 = DateTime::createFromFormat('M d Y',$matches[4].' '.$matches[5].' '.$matches[6] );
                                                if(!$d1||!$d2){
                                                        array_push($failed_records,array('docname'=>$docname,'reason'=>'invalid double 2 dates: '.$datestr.': '.print_r($matches,true) ) );
                                                        print "Parsed invalid date double2: $datestr : ".print_r($matches,true)."\n"; continue;
                                                }

                                                print "Converted dates (".$datestr."): ".$d1->format('Y-m-d H:i:s')." TO ".$d2->format('Y-m-d H:i:s')."\n";
                                        
					} else if(preg_match($patSingle, $datestr, $matches)){
						if(strlen($matches[3])<4) $matches[3] = '20'.$matches[3];
						$d1 = DateTime::createFromFormat('M d Y',$matches[1].' '.$matches[2].' '.$matches[3] );
						if(!$d1){
							array_push($failed_records,array('docname'=>$docname,'reason'=>'invalid single dates: '.$datestr.': '.print_r($matches,true) ) );
							print "Parsed invalid date single: ".print_r($matches,true)."\n"; continue;
						}
						print "Single date match: ".$d1->format('Y-m-d')."\n";
					} else if(preg_match($patSingleCompat, $datestr, $matches)){
						$d1 = DateTime::createFromFormat('m d Y',$matches[1].' '.$matches[2].' '.$matches[3]);
						if(!$d1){
                                                        array_push($failed_records,array('docname'=>$docname,'reason'=>'invalid single compact dates: '.$datestr.': '.print_r($matches,true) ) );
                                                        print "Parsed invalid date compact: $datestr : ".print_r($matches,true)."\n"; continue;
                                                }
						print "Single compact date match: ".$d1->format('Y-m-d')."\n";
					} else {
						array_push($failed_records,array('docname'=>$docname,'reason'=>'did not match datestr: '.print_r($datestr,true) ) );
                                                print "Couldnt match datestr ($format): ".$datestr."\n"; continue;
                                        }

					//check for times
					$t1 = null; $t2 = null;
					if($timestr && strlen($timestr)>10){
						$pat = '/(\d+\:*\d*\s*(?:a\.*m|p\.*m)*\.*)\s*(?:to|\&ndash;|\-|\–)(?:\s|&nbsp;)*(?:approximately\s)*(\d+\:*\d*\s(?:a\.*m|p\.*m)*\.*)/i';
						//$pat = '/(\d+\:*\d*\s*(?:a\.*m|p\.*m)*\.*)\s*(?:to|\-|\–)\s*/i';
						preg_match($pat, $timestr, $hmatches);
						if( count($hmatches)>2) {
							$t1 = $hmatches[1]; $t2=$hmatches[2];
							//print "Time matches: ".print_r($hmatches,true)."\n";
						} else {
							array_push($failed_records,array('docname'=>$docname,'reason'=>'could not match to times: '.print_r($timestr,true) ) );
							print "Could not match to times ($format): ".$timestr."\n".print_r($hmatches,true)."\n".print_r($tableMatches,true)."\n"; break;
						}
					} else {
						array_push($failed_records,array('docname'=>$docname,'reason'=>'times not long enoguh to match: '.print_r($timestr,true) ) );
						print "Times are not long enough to match ($format): ".$timestr." ".print_r($tableMatches,true)."\n";
						continue;
					}
					

					print "Location: ".$location."\n";
					if($d1) $content->set('field_calendar_event_date_one', $d1->format('Y-m-d')  );
					if($d2) $content->field_calendar_event_date_two->value = $d2->format('Y-m-d');
					if($t1) $content->field_calendar_time_one->value = $t1;
					if($t2) $content->field_calendar_time_two->value = $t2;
					if($location) { $content->field_calendar_location->value = $location; }
					if($center) { 
						//insert center into list
						$center_list = $content->get('field_calendar_center')->getValue();
						$center_list = addIdToArray($center_list,$center,'value');
						//print "CENTER LIST:\n".print_r($center_list,true)."\n";
						$content->set('field_calendar_center',$center_list);
					}
					$content->save();
					print "SAVED $docname [".$content->id()."] dates!\n";
					
        } else {
		array_push($failed_records,array('docname'=>$docname,'reason'=>'did not match table headers ('.$format.'): '."\n".substr($mc,0,600)."\n".print_r($matches,true) ) );
              print "Didn't match table headers $docname ($format)\n".substr($mc,0,900)."\n".print_r($matches,true)."\n";
	     continue;
          }
        $pat = '/<table.+?summary=".+?<\/table>/msi';
	if($format=='span'||$format=='spanp'){
		$pat = '/^.*?<.+?<h/msi';
		$mc = preg_replace($pat,'<h',$mc).'<h';
	} else {
        	$mc = preg_replace($pat,'',$mc);
	}
	print "Replaced mc: ".substr($mc,0,50)."\n";
	$content->field_calendar_main_content->value = $mc;
	$content->save();
	$recordUpdated = true;
        //$content['field_calendar_main_content'] = $mc;


	continue;
} //endforeach record field
if($xEditionDate && $content && strlen($xEditionDate)>4){ 
	$xDate = DateTime::createFromFormat('m/d/y h:i a',$xEditionDate);
	if($xDate){
	print "got date [".$content->id()."]: ".$xDate->format('Y-m-d H:i:s.u')." FROM: ".$content->field_advisory_event_group_date->value."\n"; 
	$content->set('field_advisory_event_group_date',$xDate->format('Y-m-d\TH:i:s'));
	$content->save();
	}
}
//if(!$recordUpdated) break;
continue;
} //endforeach resultset

print "# of Failed Records: ".count($failed_records)." out of ".$record_i."\n";
if(!$single_docname) file_put_contents("/var/www/html/fda/advisory_committee/logs/failed_dates",print_r($failed_records,true));
