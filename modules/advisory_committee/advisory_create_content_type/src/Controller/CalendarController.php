<?php
/**
* @file
* Contains \Drupal\advisory__create_content_type\Controller\Calendar
*/

namespace Drupal\advisory__create_content_type\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal;
use DateTime;


function sortEntriesByTimestamp($a, $b){
        if($a['timestamp']==$b['timestamp']) return 0;
        return ($a['timestamp']<$b['timestamp'])?-1:1;
}


class CalendarController extends ControllerBase {

	/**
	* {@inheritdoc}
	*/
	public function content(Request $request) {
		$build = array(
			'#type' => 'markup',
			'#markup' => t('Advisory Calendar'),
		);
		return $this->year($request, 'default');
		//return $build;
	}

	public static function entriesByTimestamp($a, $b){
		if($a['timestamp']==$b['timestamp']) return 0;
		return ($a['timestamp']<$b['timestamp'])?1:-1;
	}

	public function yearWithTwig(Request $request, $year) {
		return array(
			'#theme' => 'advisory_calendar',
			'#main' => 'main content',
			'#leftnav' => 'left navbar',
		);
	}


	public function year(Request $request, $year) {
		//return $this->yearWithTwig($request, $year);
		//return $this->not_found($year);
		/*return array(
			'#theme' => 'fda_theme',
			'#content' => $year,
		);*/
		$isDefault = ($year=='default')?true:false;
		if($isDefault){ $year = date('Y'); }
		else if( preg_match('/(\d\d\d\d)\//', $year, $matches) ){
			$year = $matches[1];
		} else if( preg_match('/([\d\w]+?)\.htm/', $year, $matches) ){
			return $this->single($request, $matches[1]);
		} 
		$yearsub = '/'.substr($year,2,2).' ';
		$ids = Drupal::entityQuery('node')
			->condition('type','advisory_committee_content')
			->condition('field_advisory_datetime',$year,'CONTAINS')
			->condition('adv_xeditiondate',null,'<>')
			->execute();
		$id_keys = array_keys($ids);
		if($isDefault){
			$year = date("Y", strtotime("-1 year"));
			$lastIds = Drupal::entityQuery('node')
                        ->condition('type','advisory_committee_content')
                        ->condition('field_advisory_datetime',$year,'CONTAINS')
			->condition('adv_xeditiondate',null,'<>')
                        ->execute();
			$lastIds = array_keys($lastIds);
			$id_keys = array_merge($id_keys, $lastIds);
		}
		$entries = \Drupal::entityManager()->getStorage('node')->loadMultiple($id_keys);
		$entries_dated = array(); $entry_month_years = array();
		//$block = \Drupal\block\Entity\Block::load('15');
		//$block_output = \Drupal::entityManager()->getViewBuilder('block')->view($block);
		//$block_output = \Drupal::service('renderer')->render($block_output);
		$block_output = '';
		$html = '<div class="col-md-6 col-md-push-3 middle-column"><article>';
		
		foreach($entries as $entry){
			$doctitle = $entry->field_advisory_doctitle->value;
			$dt = null; $monthyear = null; 
			//if(preg_match('/^(\w+)\s(\d+),\s(\d+)\:/', $doctitle, $matches) ){
			//	$dt = DateTime::createFromFormat('F d Y',$matches[1].' '.$matches[2].' '.$matches[3]);
			if($entry->field_advisory_datetime->value) {
				$dt = DateTime::createFromFormat('Y-m-d', $entry->field_advisory_datetime->value);
				$monthyear = $dt->format('F Y');
			} else {
				continue;
			}
			//$html .= '<li>'.$entry->field_advisory_doctitle->value.'</li>'; continue;
			//$html .= '<li>'.$entry->get('field_advisory_doctitle').'</li>';
		
			$entry_meta = array(
				'title'=>$doctitle,
				'month'=>$monthyear,
				'timestamp'=>$dt->getTimestamp(),
				'url'=> $request->getBaseUrl().'/AdvisoryCommittees/Calendar/'.$entry->field_advisory_docname->value.'.htm'
				);
			if(!isset($entry_month_years[$monthyear]) ){
				$entry_month_years[$monthyear] = array(
				'timestamp'=>$entry_meta['timestamp'],
				'monthyear' => $monthyear,
				'entries'=>array());
			}
			array_push($entry_month_years[$monthyear]['entries'], $entry_meta);
			array_push($entries_dated, $entry_meta);
		}
		//usort($entries_dated, array("CalendarController","sortEntriesByTimestamp"));
		usort($entries_dated, 'self::entriesByTimestamp');
		$entry_month_years_sorted = array();
		foreach($entry_month_years as $monthyear=>$val){
			usort($entry_month_years[$monthyear]['entries'],'self::entriesByTimestamp');
			array_push($entry_month_years_sorted, $entry_month_years[$monthyear] );
		}
		usort($entry_month_years_sorted, 'self::entriesByTimestamp');
		$currentMonth = null;
		foreach($entries_dated as $entry){
			if($entry['month']!=$currentMonth){
				if($currentMonth!=null){
					$html .= "</ul>\n</div>\n</div>";
				}
				$currentMonth = $entry['month'];
				$html .= "<div class='panel panel-default box'>\n"
					."<div class='panel-heading'>\n<h2 class='panel-title'>".$currentMonth."</h2></div>"
					."<div class='panel-body'>\n<ul>";
			}
			$html .= '<li><a href="'.$entry['url'].'">'.$entry['title'].'</a></li>';
		}
		if($currentMonth != null) $html .= '</ul></div></div>';
		
		$html .= '</article></div>';
		//sidebars
		$html .= '<div class="col-md-3 col-md-push-3 right-column">'
		.'<div class="panel panel-default box" id="right_col_box4"><div class="panel-heading"><h3 class="panel-title">Past Calendars</h3></div><div class="panel-body"><ul>
	<li><a href="http://www.fda.gov/oc/advisory/accalendar/2008/2008ACcalendar.html" target="_blank"><linktitle>Advisory Committee Calendars Prior to 2009</linktitle></a>
	</li>
		    		</ul>
				  </div>
				</div>';
		$html .= '</div>';
		//left year links
		$html .= '<div class="col-md-3 col-md-pull-9 left-column">'.$block_output.'</div>';
		
		$build = array(
			'#type' => 'markup',
			'#markup' => $html,
		);
		return array(
                        '#theme' => 'advisory_calendar',
                        '#main' => 'year: '.$year.' #entries: '.count($entries),
			'#show_right_nav' => true,
//			'#resources_for_you' => $resources_for_you,
                        '#leftnav' => $block_output,
			'#months_organized' => $entry_month_years_sorted
                );
		//return $build;
	}

	// matched route Calendar/{docname}.htm, display single entry
	public function single(Request $request, $docname) {
		//return array('#markup'=>'test: '.$docname);
		$docname = strtoupper($docname);
		$ids = Drupal::entityQuery('node')
                        ->condition('type','advisory_committee_content')
                        ->condition('field_advisory_docname',$docname)
                        ->execute();
		$block_output='<!-- docname: '.$docname.'-->'; $block=false;
		//$block = \Drupal\block\Entity\Block::load('advisorycommitteefolioentry');
                if($block){
		$block_output = \Drupal::entityManager()->getViewBuilder('block')->view($block);
                $block_output = 'block: '.\Drupal::service('renderer')->render($block_output);
		}
		//$block_output = preg_replace('/^<div.+item">/','', $block_output->__toString() );
		//$block_output = print_r($block_output,true);
		//$block_output = substr(substr($block_output->__toString(),558),0,-150);
		//$block_output = '';

		if(!$ids || count($ids)<1){
			return $this->not_found($docname,$block_output);
		}
                $id_keys = array_keys($ids);
		$entry = \Drupal::entityManager()->getStorage('node')->load($ids[$id_keys[0]]);
		$html = $entry->field_advisory_maincontent->value;		

		$allFields = $entry->getFields(false);
		$block_output = 'fields: ';
		foreach($allFields as $fname=>$fieldlist){
			if("advf"!= substr($fname,0,4) ) continue;
			$val = unserialize($fieldlist->value);
			//if(!is_array($fieldlist->value) ) continue;
			//if(empty($fieldlist->value->children) ) continue;
			$block_output.= '-- '.$fname.' = '.$val." --\n";
		}

		
		$resourcesforyou = $entry->get('advf_resourcesforyou')->getValue();
		$resourcesformatted = array();
		//$view_builder = \Drupal::entityManager()->getViewBuilder('advisory_folio_list_item');
		foreach($resourcesforyou as $res){
			$list_item =  \Drupal::entityManager()->getStorage('node')->load($res['target_id']);
			if(!$list_item) continue;
			$linkval = $list_item->get('folio_link')->getValue();
			$lirender = "<a href=\"".$linkval[0]['uri']."\"><linktitle>".$linkval[0]['title']."</linktitle></a>";
			//$lirender = $view_builder->view($list_item);
			//array_push($resourcesformatted, node_view($list_item) );
			array_push($resourcesformatted, array( '#markup'=>$lirender ) );
		}
		//$resourcesforyou = print_r($resourcesforyou,true);
		/*
		for($i=0;$i<count($resourcesforyou);$i++){
			$resourcesforyou[i] = print_r($resourcesforyou[i],true);
		}*/
		
		return array(
                        '#theme' => 'advisory_content',
                        '#main' => $html,
			'#resources_for_you' => $resourcesformatted,
			'#doctitle' => $entry->adv_ddoctitle->value,
                        '#leftnav' => '<!-- single loaded -->',
                );
	}

	private function not_found($docname,$block_output){
		//throw new  NotFoundHttpException();
		$main = 'not found: '.$docname;
		return array(
                        '#theme' => 'advisory_calendar',
                        '#main' => $main,
                        '#show_right_nav' => false,
                        '#leftnav' => '<!-- not_found! -->'.$block_output,
                        '#months_organized' => null
                );

	}

}

function asortEntriesByTimestamp($a, $b){
	if($a['timestamp']==$b['timestamp']) return 0;
        return ($a['timestamp']<$b['timestamp'])?-1:1;
}
?>
