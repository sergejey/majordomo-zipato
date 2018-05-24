<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['zipatodevices_qry'];
  } else {
   $session->data['zipatodevices_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_zipatodevices="TITLE";
  $out['SORTBY']=$sortby_zipatodevices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM zipatodevices WHERE $qry ORDER BY ".$sortby_zipatodevices);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
    $details='';
    $endPoints=SQLSelect("SELECT ID, TITLE FROM zipatoendpoints WHERE DEVICE_ID=".$res[$i]['ID']);
    $totale=count($endPoints);
    for($ie=0;$ie<$totale;$ie++) {
     $details.=$endPoints[$ie]['TITLE'].' ';
     $attributes = SQLSelect("SELECT * FROM zipatocommands WHERE ENDPOINT_ID=".$endPoints[$ie]['ID']);
     $totala=count($attributes);
     for($ia=0;$ia<$totala;$ia++) {
      $details.=$attributes[$ia]['TITLE'].': '.$attributes[$ia]['VALUE'];
      if ($attributes[$ia]['LINKED_OBJECT']!='' && $attributes[$ia]['LINKED_PROPERTY']!='') {
       $details.=' (<b>'.$attributes[$ia]['LINKED_OBJECT'].'.'.$attributes[$ia]['LINKED_PROPERTY'].'</b>)';
      }
      $details.='; ';
     }
     $details.=' ';
    }
    $res[$i]['DETAILS']=$details;
   }
   $out['RESULT']=$res;
  }
