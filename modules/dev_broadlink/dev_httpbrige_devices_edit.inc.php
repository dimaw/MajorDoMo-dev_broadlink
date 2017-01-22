<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='dev_httpbrige_devices';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='learn') {
	if ($this->config['API_URL']=='httpbrige') {
	   $api_command=$this->config['API_URL'].'/?devMAC='.$rec['MAC'].'&action=study';
	   getUrl($api_command);
	   $out['MESSAGE']='Режим обучения';
	} else {
			require(DIR_MODULES.$this->name.'/broadlink.class.php');
			$json = array();
			$json['code'] = -1;
			$rm = Broadlink::CreateDevice($rec['IP'], $rec['MAC'], 80, $rec['DEVTYPE']);
			$rm->Auth();
			$rm->Enter_learning();
			$out['MESSAGE']='Режим обучения';
			sleep(10);
			$json['hex'] = $rm->Check_data();
			$json['code'] = 1;
			$json['hex_number'] = '';
				foreach ($json['hex'] as $value) {
					$json['hex_number'] .= sprintf("%02x", $value);
				}
				if(count($json['hex']) > 0){
				$prop=array('TITLE'=>'new_command','VALUE'=>$json['hex_number'],'DEVICE_ID'=>$rec['ID'],);
				$new_id=SQLInsert('dev_broadlink_commands',$prop);
				}
			$out['MESSAGE']='Команда записана';
	}
  }
  if ($this->mode=='save_code') {
   //$api_command=$this->config['API_URL'].'/?devMAC='. $rec['MAC'].'&action=save&name='.$this->code_name;
   //getUrl($api_command);
   $out['MESSAGE']='Сохранение команд пока не работает. Для сохранения последней команды используйте ссылку '.$this->config['API_URL'].'/?devMAC='.$rec['MAC'].'&action=save&name='.'<имя команды>';
  }
  if ($this->mode=='sp_on') {
   $api_command=$this->config['API_URL'].'/?devMAC='.$rec['MAC'].'&action=on';
   getUrl($api_command);
  }
  if ($this->mode=='sp_off') {
   $api_command=$this->config['API_URL'].'/?devMAC='.$rec['MAC'].'&action=off';
   getUrl($api_command);
  }
  if ($this->mode=='sp_light_on') {
   $api_command=$this->config['API_URL'].'/?devMAC='.$rec['MAC'].'&action=&action=lighton';
   getUrl($api_command);
  }
  if ($this->mode=='sp_light_off') {
   $api_command=$this->config['API_URL'].'/?devMAC='.$rec['MAC'].'&action=&action=lightoff';
   getUrl($api_command);
  }
  if ($this->mode=='update') {
   $ok=1;
   
   if ($this->tab=='') {
  //updating 'LANG_TITLE' (varchar, required)
   //updating 'TYPE' (varchar)
   global $type;
   $rec['TYPE']=$type;
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }
  //updating 'IP' (varchar)
   global $ip;
   $rec['IP']=$ip;
  //updating 'DEVTYPE' (varchar)
   global $devtype;
   $rec['DEVTYPE']=$devtype;
  //updating 'MAC' (varchar)
   global $mac;
   $rec['MAC']=$mac;
  //updating 'LANG_LINKED_OBJECT' (varchar)
   global $linked_object;
   $rec['LINKED_OBJECT']=$linked_object;
  //updating 'LANG_LINKED_PROPERTY' (varchar)
   global $linked_property;
   $rec['LINKED_PROPERTY']=$linked_property;
  //updating 'LANG_METHOD' (varchar)
   global $linked_method;
   $rec['LINKED_METHOD']=$linked_method;
  //updating 'LANG_UPDATED' (datetime)
   global $updated_date;
   global $updated_minutes;
   global $updated_hours;
   $rec['UPDATED']=toDBDate($updated_date)." $updated_hours:$updated_minutes:00";
   }
   
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
		if ($rec['TYPE'] == 'sp2' || $rec['TYPE'] == 'spmini' || $rec['TYPE'] == 'sp3') {
			 sg($rec['LINKED_OBJECT'].'.'.'status', '');
			 addLinkedProperty($rec['LINKED_OBJECT'], 'status', $this->name);
		}
		if ($rec['TYPE'] == 'sp3') {
			 sg($rec['LINKED_OBJECT'].'.'.'lightstatus', '');
			 addLinkedProperty($rec['LINKED_OBJECT'], 'lightstatus', $this->name);
		}
    }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  // step: default
  if ($this->tab=='') {
  if ($rec['UPDATED']!='') {
   $tmp=explode(' ', $rec['UPDATED']);
   $out['UPDATED_DATE']=fromDBDate($tmp[0]);
   $tmp2=explode(':', $tmp[1]);
   $updated_hours=$tmp2[0];
   $updated_minutes=$tmp2[1];
  }
  for($i=0;$i<60;$i++) {
   $title=$i;
   if ($i<10) $title="0$i";
   if ($title==$updated_minutes) {
    $out['UPDATED_MINUTES'][]=array('TITLE'=>$title, 'SELECTED'=>1);
   } else {
    $out['UPDATED_MINUTES'][]=array('TITLE'=>$title);
   }
  }
  for($i=0;$i<24;$i++) {
   $title=$i;
   if ($i<10) $title="0$i";
   if ($title==$updated_hours) {
    $out['UPDATED_HOURS'][]=array('TITLE'=>$title, 'SELECTED'=>1);
   } else {
    $out['UPDATED_HOURS'][]=array('TITLE'=>$title);
   }
  }
  }
  
    if ($this->tab=='data') {
		$this->getConfig();
   $new_id=0;
   if ($this->mode=='update') {
    global $title_new;
	if ($title_new) {
	 $prop=array('TITLE'=>$title_new,'DEVICE_ID'=>$rec['ID']);
	 $new_id=SQLInsert('dev_broadlink_commands',$prop);
	}
   }
   global $delete_id;
   if ($delete_id) {
    SQLExec("DELETE FROM dev_broadlink_commands WHERE ID='".(int)$delete_id."'");
   }
   $properties=SQLSelect("SELECT * FROM dev_broadlink_commands WHERE DEVICE_ID='".$rec['ID']."' ORDER BY ID");
   $total=count($properties);
   for($i=0;$i<$total;$i++) {
    if ($properties[$i]['ID']==$new_id) continue;
    if ($this->mode=='update') {
      global ${'title'.$properties[$i]['ID']};
      $properties[$i]['TITLE']=trim(${'title'.$properties[$i]['ID']});
      global ${'value'.$properties[$i]['ID']};
      $properties[$i]['VALUE']=trim(${'value'.$properties[$i]['ID']});
      global ${'linked_object'.$properties[$i]['ID']};
      $properties[$i]['LINKED_OBJECT']=trim(${'linked_object'.$properties[$i]['ID']});
      global ${'linked_property'.$properties[$i]['ID']};
      $properties[$i]['LINKED_PROPERTY']=trim(${'linked_property'.$properties[$i]['ID']});
      SQLUpdate('dev_broadlink_commands', $properties[$i]);
      $old_linked_object=$properties[$i]['LINKED_OBJECT'];
      $old_linked_property=$properties[$i]['LINKED_PROPERTY'];
      if ($old_linked_object && $old_linked_object!=$properties[$i]['LINKED_OBJECT'] && $old_linked_property && $old_linked_property!=$properties[$i]['LINKED_PROPERTY']) {
       removeLinkedProperty($old_linked_object, $old_linked_property, $this->name);
      }
      if ($properties[$i]['LINKED_OBJECT'] && $properties[$i]['LINKED_PROPERTY']) {
       addLinkedProperty($properties[$i]['LINKED_OBJECT'], $properties[$i]['LINKED_PROPERTY'], $this->name);
      }
     }
	$properties[$i]['API_METHOD']=$this->config['API_METHOD'];
   }
   $out['PROPERTIES']=$properties;
  }
  
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);