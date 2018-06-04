<?php
/**
* Zipato
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 20:05:25 [May 22, 2018])
*/
//
//
class zipato extends module {

    private $jsessionid;

/**
* zipato
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="zipato";
  $this->title="Zipato";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 $this->getConfig();
 $out['API_URL']=$this->config['API_URL'];
 $out['API_USERNAME']=$this->config['API_USERNAME'];
 $out['API_PASSWORD']=$this->config['API_PASSWORD'];
 if ($this->view_mode=='update_settings') {
   global $api_url;
   $this->config['API_URL']=$api_url;
   global $api_username;
   $this->config['API_USERNAME']=$api_username;
   global $api_password;
   $this->config['API_PASSWORD']=$api_password;
   $this->saveConfig();
   if ($this->config['API_URL']!='') {
       $this->sync();
   }
     $this->redirect();
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }

 if ($this->mode=='sync') {
     $this->sync();
     $this->redirect("?");
 }

 if ($this->data_source=='zipatodevices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_zipatodevices') {

      if ($this->config['API_URL']) {
          if ($this->apiLogin()) {
              $out['API_ONLINE']=1;
          }
      }

   $this->search_zipatodevices($out);
  }
  if ($this->view_mode=='edit_zipatodevices') {
   $this->edit_zipatodevices($out, $this->id);
  }
  if ($this->view_mode=='delete_zipatodevices') {
   $this->delete_zipatodevices($this->id);
   $this->redirect("?data_source=zipatodevices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='zipatocommands') {
  if ($this->view_mode=='' || $this->view_mode=='search_zipatocommands') {
   $this->search_zipatocommands($out);
  }
  if ($this->view_mode=='edit_zipatocommands') {
   $this->edit_zipatocommands($out, $this->id);
  }
 }
}

function apiLogin() {
    $ip = $this->config['API_URL'];
    $login = $this->config['API_USERNAME'];
    $password = $this->config['API_PASSWORD'];
    $url_base ='http://'.$ip.':8080/zipato-web';

    $url = $url_base .'/json/init';
    $output = getURL($url);
    $result = json_decode($output,true);

    if (!$result['success']) return false;

    $jsessionid = $result['jsessionid'];
    $this->jsessionid = $jsessionid;
    $nonce = $result['nonce'];

    $temp = sha1($password);
    $token = sha1($nonce.$temp);
    $url = $url_base.'/v2/user/login?username='.urlencode($login)."&token=".$token;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json;'));
    curl_setopt($ch, CURLOPT_COOKIE, 'JSESSIONID='.$jsessionid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $result = json_decode ($output,true);

    if ($result['success']) {
        return true;
    } else {
        return false;
    }

}

function sendRequest($path, $method='GET', $parameters=0) {
    if (!$this->jsessionid) {
        $logged = $this->apiLogin();
        if (!$logged) {
            echo "Failed to login";
            return false;
        }
    }
    $jsessionid = $this->jsessionid;
    $ip = $this->config['API_URL'];
    $url_base ='http://'.$ip.':8080/zipato-web';
    $url = $url_base.$path;


    /*
    if (is_array($parameters)) {
        $url_parameters = http_build_query($parameters);
        $url .= '?'. $url_parameters;
    }
    */


    //echo ("\nRequest $method $url");
    if (is_array($parameters)) {
        //echo("\nData: ".json_encode($parameters));
    }

    $ch = curl_init($url);
    if ($method!='GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $jsonData=json_encode($parameters);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json;','Content-Length: ' . strlen($jsonData)));
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json;'));
    }
    curl_setopt($ch, CURLOPT_COOKIE, 'JSESSIONID='.$jsessionid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    //echo "\nResponse: $output";
    //DebMes("Response $output",'zipato');
    $result = json_decode ($output,true);
    curl_close($ch);
    return $result;
}

function processAttributeData($uuid, $data) {

    if (!is_array($data) || !isset($data['value'])) {
        return false;
    }
    $rec = SQLSelectOne("SELECT * FROM zipatocommands WHERE UUID='".$uuid."'");
    if (!$rec['ID']) {
        return false;
    }

    $value = $data['value'];
    $updated = strtotime($data['timestamp']);
    $rec['VALUE']=$value;
    $rec['UPDATED']=date('Y-m-d H:i:s',$updated);
    SQLUpdate('zipatocommands',$rec);

    if ($rec['LINKED_OBJECT'] && $rec['LINKED_PROPERTY']) {
        setGlobal($rec['LINKED_OBJECT'].'.'.$rec['LINKED_PROPERTY'],$value);
    }
    return true;
}

function pollAttributes() {
    $data = $this->sendRequest('/v2/attributes');
}

function refreshDevice($device_id) {

    $device=SQLSelectOne("SELECT * FROM zipatodevices WHERE ID=".(int)$device_id);
    $uuid=$device['UUID'];

    if (!$uuid) {
        return false;
    }

    $data = $this->sendRequest('/v2/devices/'.$uuid.'/status');

    if (isset($data['state'])) {
        $device['MAINSPOWER']=$data['state']['mainsPower'];
        $device['BATTERYLEVEL']=$data['state']['batteryLevel'];
        $device['ONLINESTATE']=$data['state']['onlineState'];
        SQLUpdate('zipatodevices',$device);
    }
    $attributes = SQLSelect("SELECT * FROM zipatocommands WHERE DEVICE_ID=".(int)$device['ID']);
    $totala=count($attributes);
    for($ia=0;$ia<$totala;$ia++) {
        $uuid = $attributes[$ia]['UUID'];
        $data = $this->sendRequest('/v2/attributes/'.$uuid.'/value');
        $this->processAttributeData($uuid,$data);
    }
}

function sync() {
    if ($this->apiLogin()) {
        $data = $this->sendRequest('/v2/devices');
        if (is_array($data)) {
            $total = count($data);
            $foundDevices = array();
            for($i=0;$i<$total;$i++) {
                $uuid = $data[$i]['uuid'];
                $title = trim($data[$i]['name']);
                if (!$uuid) {
                    continue;
                }
                $device = SQLSelectOne("SELECT * FROM zipatodevices WHERE UUID = '".DBSafe($uuid)."'");
                $device['UUID']=$uuid;
                $device['TITLE']=$title;
                if (!$device['ID']) {
                    $device['ID'] = SQLInsert('zipatodevices',$device);
                } else {
                    SQLUpdate('zipatodevices',$device);
                }
                $endPoints = $this->sendRequest('/v2/devices/'.$uuid.'/endpoints');
                $totale=count($endPoints);
                for($ie=0;$ie<$totale;$ie++) {
                    $endPoint=SQLSelectOne("SELECT * FROM zipatoendpoints WHERE DEVICE_ID='".$device['ID']."' AND UUID='".DBSafe($endPoints[$ie]['uuid'])."'");
                    $endPoint['DEVICE_ID']=$device['ID'];
                    $endPoint['TITLE']=$endPoints[$ie]['name'];
                    $endPoint['UUID']=$endPoints[$ie]['uuid'];
                    if ($endPoint['ID']) {
                        SQLUpdate('zipatoendpoints',$endPoint);
                    } else {
                        $endPoint['ID'] = SQLInsert('zipatoendpoints',$endPoint);
                    }
                    $endPointData = $this->sendRequest('/v2/endpoints/'.$endPoints[$ie]['uuid'].'/config');//.'?type=true&actions=true'
                    $deviceType=$endPointData['genericDevClass'];
                    if ($deviceType && $endPoint['ENDPOINT_TYPE']!=$deviceType) {
                        $endPoint['ENDPOINT_TYPE']=$deviceType;
                        SQLUpdate('zipatoendpoints',$endPoint);
                    }

                    $endPointAttributes = $this->sendRequest('/v2/endpoints/'.$endPoints[$ie]['uuid'].'?attributes=true');//.'?type=true&actions=true'
                    $totala = count($endPointAttributes['attributes']);
                    for($ia=0;$ia<$totala;$ia++) {
                        $attribute = $endPointAttributes['attributes'][$ia];
                        $command = SQLSelectOne("SELECT * FROM zipatocommands WHERE ENDPOINT_ID=".$endPoint['ID']." AND UUID='".DBSafe($attribute['uuid'])."'");
                        $command['DEVICE_ID']=$endPoint['DEVICE_ID'];
                        $command['ENDPOINT_ID']=$endPoint['ID'];
                        $command['UUID']=$attribute['uuid'];
                        $command['TITLE']=$attribute['attributeName'];
                        if ($command['ID']) {
                            SQLUpdate('zipatocommands',$command);
                        } else {
                            $command['ID']=SQLInsert('zipatocommands',$command);
                        }
                    }
                }
                $this->refreshDevice($device['ID']);
                $foundDevices[]=$device['ID'];
            }
        }
    }
    //exit;
}

/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* zipatodevices search
*
* @access public
*/
 function search_zipatodevices(&$out) {
  require(DIR_MODULES.$this->name.'/zipatodevices_search.inc.php');
 }
/**
* zipatodevices edit/add
*
* @access public
*/
 function edit_zipatodevices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/zipatodevices_edit.inc.php');
 }
/**
* zipatodevices delete record
*
* @access public
*/
 function delete_zipatodevices($id) {
  $rec=SQLSelectOne("SELECT * FROM zipatodevices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM zipatocommands WHERE DEVICE_ID='".$rec['ID']."'");
  SQLExec("DELETE FROM zipatoendpoints WHERE DEVICE_ID='".$rec['ID']."'");
  SQLExec("DELETE FROM zipatodevices WHERE ID='".$rec['ID']."'");
 }
/**
* zipatocommands search
*
* @access public
*/
 function search_zipatocommands(&$out) {
  require(DIR_MODULES.$this->name.'/zipatocommands_search.inc.php');
 }
/**
* zipatocommands edit/add
*
* @access public
*/
 function edit_zipatocommands(&$out, $id) {
  require(DIR_MODULES.$this->name.'/zipatocommands_edit.inc.php');
 }
 function propertySetHandle($object, $property, $value) {
  $this->getConfig();
   $table='zipatocommands';
   $properties=SQLSelect("SELECT * FROM $table WHERE LINKED_OBJECT LIKE '".DBSafe($object)."' AND LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
     //to-do
        $uuid=$properties[$i]['UUID'];
        $params=array('uuid'=>$uuid,'value'=>$value);
        $data = $this->sendRequest('/v2/attributes/'.$uuid.'/value','PUT',$params);
        //DebMes($data,'zipato');
        //$endPointAttributes = $this->sendRequest('/v2/endpoints/'.$endPoints[$ie]['uuid'].'?attributes=true');//.'?type=true&actions=true'
    }
   }
 }
 function processCycle() {
  $this->getConfig();
  $this->sync();
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS zipatodevices');
  SQLExec('DROP TABLE IF EXISTS zipatocommands');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
zipatodevices -
zipatocommands -
*/
  $data = <<<EOD
 zipatodevices: ID int(10) unsigned NOT NULL auto_increment
 zipatodevices: TITLE varchar(100) NOT NULL DEFAULT ''
 zipatodevices: UUID varchar(100) NOT NULL DEFAULT ''
 zipatodevices: MAINSPOWER varchar(100) NOT NULL DEFAULT '' 
 zipatodevices: BATTERYLEVEL varchar(100) NOT NULL DEFAULT ''
 zipatodevices: ONLINESTATE varchar(100) NOT NULL DEFAULT ''

 zipatoendpoints: ID int(10) unsigned NOT NULL auto_increment
 zipatoendpoints: TITLE varchar(100) NOT NULL DEFAULT ''
 zipatoendpoints: UUID varchar(100) NOT NULL DEFAULT ''
 zipatoendpoints: DEVICE_ID int(10) NOT NULL DEFAULT '0'  
 zipatoendpoints: ENDPOINT_TYPE varchar(100) NOT NULL DEFAULT ''

 zipatocommands: ID int(10) unsigned NOT NULL auto_increment
 zipatocommands: TITLE varchar(100) NOT NULL DEFAULT ''
 zipatocommands: UUID varchar(100) NOT NULL DEFAULT '' 
 zipatocommands: VALUE varchar(255) NOT NULL DEFAULT ''
 zipatocommands: ENDPOINT_ID int(10) NOT NULL DEFAULT '0' 
 zipatocommands: DEVICE_ID int(10) NOT NULL DEFAULT '0'
 zipatocommands: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 zipatocommands: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 zipatocommands: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWF5IDIyLCAyMDE4IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
