<?php

defined('_JEXEC') or die;

class plgSystemDumpscripts extends JPlugin {
	protected $autoloadLanguage = true;
	function OnBeforeCompileHead() {
		//not for admin!
		if (JFactory::getApplication()->isAdmin()) {
			return;
		}
		$dumpscripts_txt=trim($this->params->get('dumpscripts',''));
		$orderscripts_txt=trim($this->params->get('orderscripts',''));
		$dumpinlinescripts_txt=trim($this->params->get('dumpinlinescripts',''));
		$dumpstylesheets_txt=trim($this->params->get('dumpstylesheets',''));
		$orderstylesheets_txt=trim($this->params->get('orderstylesheets',''));
		
		if( $dumpscripts_txt==='' && $orderscripts_txt==='' && $dumpstylesheets_txt==='' && $orderstylesheets_txt==='' && $dumpinlinescripts_txt==='' )
		{
			return;
		} else {
			$data = JFactory::getDocument()->getHeadData();
			$altereddata = $data;
			foreach($data as $type=>$items) {
				if($type==='script') {
					if($dumpinlinescripts_txt !=='') {
						//step 1 remove spaces before and after ##### from plugin - see fxn replacernt
						$scriptdelimiterpattern='/(\s*)(#####)(\s*)/';
						$scriptdelimiterreplacer='$2';
						$dumpinlinescripts_txt=preg_replace($scriptdelimiterpattern,$scriptdelimiterreplacer,$dumpinlinescripts_txt);
						//step 2 create an array of each of the scripts from the plugin
						$dumpinlinescripts_arr = array_map('trim', explode('#####', $dumpinlinescripts_txt)); 
						//step 3 replace any combination of \r\n\t with 5 hashes ##### - this separates out individual lines in each script from the plugin
						$dumpinlinescripts_arr = array_map(array('plgSystemDumpscripts','_replacernt'), $dumpinlinescripts_arr); 
						foreach($items as $k=>$v) {
							if($k==='text/javascript') {
								foreach($dumpinlinescripts_arr as $dumplinescript) {
									//step 4 if there are multiple lines we will create a custom pattern of '/line[\s]+line[\s]+line/'
									if(strpos($dumplinescript,'#####')!==false) {
										$line_dumplinescript=explode('#####',$dumplinescript);
										$pattern_replacer='/' . $this->_getpattern($line_dumplinescript) . '/';
										if( preg_match($pattern_replacer,$v) ) {
											//step 5 replace the entire pattern with nothing ''
											$v = preg_replace($pattern_replacer,'', $v);
										}
									}
									//step 6 if we get here the script is on one line:
									elseif ( strpos($v, $dumplinescript)!==false ) { 
										$v=str_replace($dumplinescript,'',$v);
									}
								}
								$altereddata['script']['text/javascript']=$v;
							}
						}
					}
				}
				if($type==='scripts') {
					if( $dumpscripts_txt!=='' || $orderscripts_txt!=='') {
						//orderscripts and dumpscripts can't be used 'as is' because they can contain partial string matches
						//which is why we are using strpos
						//so its possible to dump a number of scripts with just one val, like media/jui/js!
						$dumpandorder=true;
						$dumpscripts_arr=null;
						$orderscripts_arr=null;
						if( $dumpscripts_txt!=='' ) {
							$dumpscripts_arr = explode(',',$dumpscripts_txt);
							if( $this->_countequalitems($items, $dumpscripts_arr)) {
								$dumpandorder=false; //set flag
								//in libraries>joomla>document>html>html lines 165, 167
								//we need to set $this->_sscripts to an empty array
								//because if we pass in empty $data['scripts']
								//it just looks for $this->_scripts instead
								JFactory::getDocument()->_scripts=array(); //set $this->_scripts to empty
								$altereddata['scripts']=array(); //set $data['styleSheets'] to empty
							}
						}
						//only go further if dumpandorder is still true
						if($dumpandorder) {
							if( $orderscripts_txt!=='' )  {
								$orderscripts_arr = explode(',',$orderscripts_txt);
							}
							$altereddata['scripts']=$this->_dumpandorder($items, $dumpscripts_arr, $orderscripts_arr);
						}
					}
				}
				if($type==='styleSheets') {
					if( $dumpstylesheets_txt!=='' || $orderstylesheets_txt!=='') {
						//special flag for where number of items and number of dumpscripts is equal
						//if this is the case, we don't want to run the ordering script
						//and we need to take very special action to remove the script(s)
						$dumpandorder=true;
						$dumpstylesheets_arr=null;
						$orderstylesheets_arr=null;
						if( $dumpstylesheets_txt!=='' ) {
							$dumpstylesheets_arr = explode(',',$dumpstylesheets_txt);
							//now comes the special situation where ALL is being removed
							if( $this->_countequalitems($items, $dumpstylesheets_arr)) {
								$dumpandorder=false; //set flag
								JFactory::getDocument()->_styleSheets=array(); //set $this->_styleSheets to empty
								$altereddata['styleSheets']=array(); //set $data['styleSheets'] to empty
							}
						}
						//only go further if dumpandorder is still true
						if($dumpandorder) {
							if( $orderstylesheets_txt!=='' ) {
								$orderstylesheets_arr = explode(',',$orderstylesheets_txt);
							}
							$altereddata['styleSheets']=$this->_dumpandorder($items, $dumpstylesheets_arr, $orderstylesheets_arr);
						}
					}
				}
				
			} //end foreach
			JFactory::getDocument()->setHeadData($altereddata);
		}
	}
	protected function _dumpandorder($items, $dumpit_arr, $orderit_arr) {
		//unsetthem will hold items that have to be unset (including those going to the top of the list)
		$unsetthem = array(); //will be used in either case
		//putontop will hold items that are going to be added back in, ON TOP!
		$putontop = null; //only used if we have ordering to do
		
		foreach($items as $item=>$value) {
			if(is_array($dumpit_arr)) {
				foreach($dumpit_arr as $dumpit) {
					if( $item===trim($dumpit) ||  strpos($item, trim($dumpit))!==false ) {
						//can't use unset here, messes up the indexing
						$unsetthem[$item]=$value; 
					}
				}
			}
			if(is_array($orderit_arr)) {
				foreach($orderit_arr as $orderit) {
					//watch out for strpos, because it returns the pos of the first char found, 
					//and that could be 0, ergo !== so we get a boolean and not a false negative
					if( $item===trim($orderit) ||  strpos($item, trim($orderit))!==false ) {
						//use array_search to get the key
						//we need the key so that we can keep the order of the results
						$keyvalue = array_search($orderit,$orderit_arr);
						$putontop[$keyvalue][$item]=$value; //we must have a sense of order!
						$unsetthem[$item]=$value; 
					}
				}
			}
		
		}//end item loop
		
		//lets unset now
		//we use array_diff_key to compare the key values ($items - which are the scripts)
		$items = array_diff_key($items, $unsetthem);
		//sort the orderscripts from 0-n
		if(is_array($putontop)) {
			ksort($putontop);
			//$putontop has numeric keys, so we'll want to dump that
			//and put them in a new array with the same structure as $items
			//otherwise array_merge won't work
			$addtopitems=array();
			foreach($putontop as $skey) {
				foreach($skey as $sitem=>$sval) {
					$addtopitems[$sitem]=$sval;
				}
			}
			//merge it
			$items = array_merge($addtopitems, $items);
		}
		return $items;
		//done! 
	}
	protected function _countequalitems($originalitem, $dump_arr) {
		$countoriginal=count($originalitem);
		$countnew=0;
		$item_arraykeys = array_keys($originalitem);
		foreach($item_arraykeys as $arraykey) {
			foreach($dump_arr as $dump) {
				if($arraykey===trim($dump) || strpos($arraykey, trim($dump))!==false ) {
					$countnew++;
				}
			}
		}
		//if they put in duplicates, this takes care of it and returns true
		return ($countnew >= $countoriginal);
	}
	protected function _replacernt($beforereplace) {
		//YES-note this doesn't work if you are using json_encode
		//if using json_encode prefer str_replace - create an array of \r, \n, \t (however you can't replace with precise number of hashes)
		$rpattern='/[\r\n\t]+/'; //note that [\s]+ won't work because it will replace spacebar spaces too
		return preg_replace($rpattern,'#####',$beforereplace);
		
	}
	protected function _getpattern($lines) {
		$countlines=count($lines);
		$counter=0;
		$newexpr='';
		foreach($lines as $line) {
			$newexpr.= preg_quote($line);
			$counter++;
			if($counter < $countlines) {
				$newexpr .= '[\s]+';
			}	
		}
		return $newexpr;
	}
}
