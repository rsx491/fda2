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
                $ids = Drupal::entityQuery('node')
                        ->condition('type','advisory_committee_content')
                        ->condition('field_advisory_datetime',$year."-",'CONTAINS')
                        ->execute();
                $id_keys = array_keys($ids);
                if($isDefault){
                        $year = date("Y", strtotime("-1 year"));
                        $lastIds = Drupal::entityQuery('node')
                        ->condition('type','advisory_committee_content')
                        ->condition('field_advisory_doctitle',$year,'CONTAINS')
                        ->execute();
                        $lastIds = array_keys($lastIds);
                        $id_keys = array_merge($id_keys, $lastIds);
                }
                $entries = \Drupal::entityManager()->getStorage('node')->loadMultiple($id_keys);
                $entries_dated = array(); $entry_month_years = array();
                $block = \Drupal\block\Entity\Block::load('accyearlylinks_2');
                $block_output = \Drupal::entityManager()->getViewBuilder('block')->view($block);
                $block_output = \Drupal::service('renderer')->render($block_output);
                $html = '<div class="col-md-6 col-md-push-3 middle-column"><article>';

                foreach($entries as $entry){
                        $doctitle = $entry->field_advisory_doctitle->value;
                        $dt = null; $monthyear = null;
                        if(preg_match('/^(\w+)\s(\d+),\s(\d+)\:/', $doctitle, $matches) ){
                                $dt = DateTime::createFromFormat('F d Y',$matches[1].' '.$matches[2].' '.$matches[3]);
                                $monthyear = $matches[1].' '.$matches[3];
                        } else {
                                continue;
                        }
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
                        '#main' => $html,
                        '#show_right_nav' => true,
                        '#leftnav' => $block_output,
                        '#months_organized' => $entry_month_years_sorted
                );
                //return $build;
        }
        public function single(Request $request, $docname) {
                //return array('#markup'=>'test: '.$docname);
                $docname = strtoupper($docname);
                $ids = Drupal::entityQuery('node')
                        ->condition('type','advisory_committee_content')
                        ->condition('adv_ddocname',$docname)
                        ->execute();
                $block = \Drupal\block\Entity\Block::load('accyearlylinks_2');
                $block_output = \Drupal::entityManager()->getViewBuilder('block')->view($block);
                $block_output = \Drupal::service('renderer')->render($block_output);
                //$block_output = preg_replace('/^<div.+item">/','', $block_output->__toString() );
                //$block_output = print_r($block_output,true);
                $block_output = substr(substr($block_output->__toString(),558),0,-150);

                if(!ids || count($ids)<1){
                        return $this->not_found($docname,$block_output);
                }
                $id_keys = array_keys($ids);
                $entry = \Drupal::entityManager()->getStorage('node')->load($ids[$id_keys[0]]);
                $html = $entry->field_advisory_maincontent->value;


                return array(
                        '#theme' => 'advisory_content',
                        '#main' => $html,
                        '#doctitle' => $entry->field_advisory_doctitle->value,
                        '#leftnav' => $block_output,
                );
        }

        private function not_found($docname,$blockoutput){
                //throw new  NotFoundHttpException();
                $main = 'not found: '.$docname;
                return array(
                        '#theme' => 'advisory_calendar',
                        '#main' => $main,
                        '#show_right_nav' => false,
                        '#leftnav' => $block_output,
                        '#months_organized' => $entry_month_years_sorted
                );

        }

}

function asortEntriesByTimestamp($a, $b){
        if($a['timestamp']==$b['timestamp']) return 0;
        return ($a['timestamp']<$b['timestamp'])?-1:1;
}
?>

