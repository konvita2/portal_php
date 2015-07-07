<?php
 
//predND(273654)_1430301303_SM(3336)_NK(590)_DAT(29.04.2015)_ObSumSoSkid(2594.37).pdf 
 
// use this module as
// include($_SERVER["DOCUMENT_ROOT"] . "/bitrix/arka/arka_classes.php");

CModule::IncludeModule ( "bizproc" );
CModule::IncludeModule("crm");

// sms
//include_once('XML.php');
/**
 * XML-парсер, замена для SimpleXML
 */
class XML implements ArrayAccess, IteratorAggregate, Countable {
      /**
       * Указатель на текущий элемент
       * @var XML
       */
      private $pointer;
      /**
       * Название элемента
       * @var string
       */
      private $tagName;
      /**
       * Ассоциативный массив атрибутов
       * @var array
       */
      private $attributes = array();
      /**
       * Содержимое элемента
       * @var string
       */
      private $cdata;
      /**
       * Указатель на родительский элемент
       * @var XML
       */
      private $parent;
      /**
       * Массив потомков, вида:
       * array('tag1' => array(0 =>, 1 => ...) ...)
       * @var array
       */
      private $childs = array();

      /**
       * Конструктор из строки с xml-текстом
       * или данных вида array('название', array('атрибуты'))
       * @var array|string $data
       */
      public function __construct($data) {
          if (is_array($data)) {
            list($this->tagName, $this->attributes) = $data;
          } else if (is_string($data))
              $this->parse($data);
      }

      /**
       * Метод для доступа к содержанию элемента
       * @return stirng
       */
      public function __toString() {
          return $this->cdata;
      }

      /**
       * Доступ к потомку или массиву потомков
       * @var string $name
       * @return XML|array
       */
      public function __get($name) {
          if (isset($this->childs[$name])) {
            if (count($this->childs[$name]) == 1)
                  return $this->childs[$name][0];
            else
                  return $this->childs[$name];
          }
      //    throw new Exception("UFO steals [$name]!");
      }

      /**
       * Доступ к атрибутам текущего элемента
       * @var string $offset
       * @return mixed
       */
      public function offsetGet($offset) {
          if (isset($this->attributes[$offset]))
            return $this->attributes[$offset];
            throw new Exception("Holy cow! There is'nt [$offset] attribute!");
      }

      /**
       * Проверка на существование атрибута
       * @var string $offset
       * @return bool
       */
      public function offsetExists($offset) {
          return isset($this->attributes[$offset]);
      }

      /**
       * Затычки
       */
      public function offsetSet($offset, $value) { return; }
      public function offsetUnset($offset) { return; }

      /**
       * Возвращает количество элементов с этим именем у родителя
       * @return integer
       */
      public function count() {
            if ($this->parent != null)
                  return count($this->parent->childs[$this->tagName]);
            return 1;
      }

      /**
       * Возвращает итератор по массиву одноименных элементов
       * @return ArrayIterator
       */
      public function getIterator() {
            if ($this->parent != null)
                  return new ArrayIterator($this->parent->childs[$this->tagName]);
            return new ArrayIterator(array($this));
      }

      /**
       * Получить массив атрибутов
       * @return array
       */
      public function getAttributes() {
            return $this->attributes;
      }

      /**
       * Добавить потомка
       * @var string $tag
       * @var array $attributes
       * @return XML
       */
      public function appendChild($tag, $attributes) {
          $element = new XML(array($tag, $attributes));
          $element->setParent($this);
          $this->childs[$tag][] = $element;
          return $element;
      }

      /**
       * Установить родительский элемент
       * @var XML $parent
       */
      public function setParent(XML $parent) {
          $this->parent =& $parent;
      }

      /**
       * Поулчить родительский элемент
       * @return XML
       */
      public function getParent() {
          return $this->parent;
      }

      /**
       * Установить данные элемента
       * @var string $cdata
      */
      public function setCData($cdata) {
          $this->cdata = $cdata;
      }

      /**
       * Парсим xml-строку и делаем дерево элементов
       * @var string $data
       */
      private function parse($data) {
          $this->pointer =& $this;
          $parser = xml_parser_create();
          xml_set_object($parser, $this);
          xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
          xml_set_element_handler($parser, "tag_open", "tag_close");
          xml_set_character_data_handler($parser, "cdata");
          xml_parse($parser, $data);
      }

      /**
       * При открытии тега, добавляем дите и устанавливаем указатель на него
       */
      private function tag_open($parser, $tag, $attributes) {
          $this->pointer =& $this->pointer->appendChild($tag, $attributes);
      }

      /**
       * При получении данных
       */
      private function cdata($parser, $cdata) {
          $this->pointer->setCData($cdata);
      }

      /**
       * При закрытии тега, возвращаем указатель на предка
       */
      private function tag_close($parser, $tag) {
          $this->pointer =& $this->pointer->getParent();
      }
}


class CArkaRegion
{
    // получить параметр по имени группы и по имени параметра
    // пока предполагаем числовой параметр
    // т.е. результат - число
    // если группа направильна то возвращаем 0
    private static function GetParameter($grname,$parname)
    {
	$res = 0;
	
	$arFilter = array(
	    "IBLOCK_ID" => 80,
	    "NAME" => $grname
	);
	
	$ib = new CIBlockElement ();
	$ibRes = $ib->GetList(array(), $arFilter);
	if($ob = $ibRes->GetNextElement())
	{
	    // get max_number
	    $para = 0;
	    $arFlds = $ob->GetFields();
	    $curID = $arFlds["ID"];
	    $dbRes = $ib->GetProperty(80, $curID, array(), array("CODE"=>$parname));
	    if($arProps = $dbRes->Fetch())
	    {
		$para = $arProps["VALUE"];
		$res = $para;
	    }
	}
	
	return $res;
    }
    
    // получить имя группы по ID пользователя (число)
    public static function GetGrNameByUserID($userid)
    {
	$res = '';
	$rsUser = CUser::GetByID($userid);
	$arUser = $rsUser->Fetch();
	$resID = $arUser['UF_REGION_NUMERATOR'];
	$resList = CIBLockElement::GetByID($resID);
	if($arRes = $resList->GetNext()){
	    $res = $arRes['NAME'];
	}	
	return $res;
    }
    
    // получить макс номер
    public static function GetMaxNumber($grname)
    {
	return CArkaRegion::GetParameter($grname,"max_number");
    }    
    
    // получить мин номер
    public static function GetMinNumber($grname)
    {
	return CArkaRegion::GetParameter($grname,"min_number");
    }
        
    // получить коэф материалов
    public static function GetKMater($grname)
    {
	return CArkaRegion::GetParameter($grname,"k_mater");
    }
    
    // получить коэф работы
    public static function GetKWork($grname)
    {
	return CArkaRegion::GetParameter($grname,"k_work");
    }    
    
    // получить коэф
    public static function GetK($grname)
    {
	return CArkaRegion::GetParameter($grname,"k");
    }
    
    
    // получить следующий номер соответсвующий группе
    // $grname - имя группы
    // возвращает номер
    // или 0 если группа была указана неправильно
    // или -1 если достигнут максимальный номер
    public static function GetNextNumber($grname)
    {
	$result = 0;
	
	$arFilter = array(
	    "IBLOCK_ID" => 80,
	    "NAME" => $grname
	);
	
	$ib = new CIBlockElement ();
	$ibRes = $ib->GetList(array(), $arFilter);
	if($ob = $ibRes->GetNextElement())
	{
	    // get max_number
	    $max_number = 0;
	    $arFlds = $ob->GetFields();
	    $curID = $arFlds["ID"];
	    $dbRes = $ib->GetProperty(80, $curID, array(), array("CODE"=>"max_number"));
	    if($arProps = $dbRes->Fetch())
	    {
		$max_number = $arProps["VALUE"];
		
	    }
	    
	    // get result
	    $arFlds = $ob->GetFields();
	    $curID = $arFlds["ID"];
	    $dbRes = $ib->GetProperty(80, $curID, array(), array("CODE"=>"next_number"));
	    
	    if($arProps = $dbRes->Fetch())
	    {
		$curValue = $arProps["VALUE"];
		if($curValue <= $max_number){
		    $result = $curValue;
		    CIBlockElement::SetPropertyValues($curID, 80, $curValue+1, 'next_number');
		    
		}
		else
		{
		    $result = -1;
		}	
	    }	    
	}		
	return $result;
    }
}

/*
Класс для работы со списком УчетВремениНаПросчет
*/
class CArkaConstrWork
{
    
    /*
    Создать новую запись в списке УчетВремениНаПросчет
    id пользователя, id сделки, наименование
    */
    public static function RegisterStart($user_id,$deal_id,$nam = '')
    {
        $res = 0;
        // 
        $name_el = $nam;
        $opis_el = $nam + ' start';
        $ID_INFOBLOCK = 65;
        
        $dt = new DateTime("now");
		$curTime = $dt->getTimestamp();
        
        $prop_el = array(
            "user" => $user_id,
            "deal" => $deal_id,
            "timestart" => ConvertTimeStamp(time(),FULL),  
        );
        
        $arLoad = array(
            "IBLOCK_ID" => $ID_INFOBLOCK,
            "PROPERTY_VALUES" => $prop_el,
            "NAME" => $name_el,
            "ACTIVE" => "Y",
            "DETAIL_TEXT" => $opis_el,            
        );
        
        $element = new CIBlockElement();
        if($element_id = $element->Add($arLoad)){
            $res = $element_id;
        }

        
        return $res;                
    } 
    
    /*
    Найти запись с незаполненной датой конца и выполнить фиксацию
    id пользователя, id сделки
    retunrs true if success, else false
    */
    public static function RegisterStop($user_id,$deal_id)
    {
        $res = false;       
        
        $iblock_id = 65;
        $stop_code = "timestop";
        $dur_code = "duration";
        $qpos_code = "qpositions"; // количество позиций
        
        $arFilter = array(
            "IBLOCK_ID" => $iblock_id,
            "PROPERTY_USER" => $user_id,
            "PROPERTY_DEAL" => $deal_id,
        );
        
        $rsItems = CIBlockElement::GetList(array("SORT"=>"ASC"), $arFilter, false, false, array());
        while(($ob = $rsItems->GetNextElement()) && (!$res) ){
            $arFields = $ob->GetFields();
            $arProps  = $ob->GetProperties();
            
            $element_id = $arFields["ID"]; 
            
            //define timestart
            $found = false;
            foreach($arProps as $kk => $vv){
                if($vv["CODE"] == "timestart"){
                    $timestart = $vv["VALUE"];
                    $found = true;
                    break;
                }                
            }

            //получить количество позиций в сделке
            $qpos = CArkaCrm::GetQPosByDealID($deal_id);

            
            if(!empty($timestart) && $found){
                foreach($arProps as $key => $val){
                    $cod = $val["CODE"];
                    if($cod == "timestop"){
                        $timestop = $val["VALUE"];
                        if(empty($timestop)){
                            // update this record with curr time as timestop      
                            $curr_time = time();
                            $prev_time = MakeTimeStamp($timestart);
                            $period = $curr_time - $prev_time;
                            
                            $curr_time_site = ConvertTimeStamp($curr_time,FULL);                 
                            CIBlockElement::SetPropertyValues($element_id, $iblock_id, $curr_time_site, $stop_code);  
                            CIBlockElement::SetPropertyValues($element_id, $iblock_id, $period, $dur_code);
                            CIBlockElement::SetPropertyValues($element_id, $iblock_id, $period, $dur_code);
                            $res = true;                      
                            break;    
                        }   
                    }                
                }    
            }
            
            
        }
        
        return $res;                
    }
    
      
}



class CArkaNumerator {
	
	// get next number
	//
	// returns current number and increments value
	// if error returns 0
	// $nam - name of element
	public static function GetNextNumber($nam) {
		$result = 0;
		
		$ib = new CIBlockElement ();
		
		$arFilter = array (
				"IBLOCK_ID" => 68,
				"NAME" => $nam 
		);
		
		$ibres = $ib->GetList ( array (), $arFilter );
		if ($ob = $ibres->GetNextElement ()) {
			$arFlds = $ob->GetFields ();
			$curID = $arFlds ["ID"];
			$dbRes = $ib->GetProperty ( 68, $curID, array (), array (
					"CODE" => "valu" 
			) );
			if ($arProps = $dbRes->Fetch ()) {
				$curValue = $arProps ['VALUE'];
				
				$result = $curValue;
				
				$ib->SetPropertyValues ( $curID, 68, $curValue + 1, 'VALU' );
			}
		}
		
		return $result;
	}
	
	// set numerator to 1
	//
	// nam - name of element
	//
	// returns 1 on success, returns 0 on error
	public static function Reset($nam) {
		$result = 0;
		
		$ib = new CIBlockElement ();
		
		$arFilter = array (
				"IBLOCK_ID" => 68,
				"NAME" => $nam 
		);
		
		$ibres = $ib->GetList ( array (), $arFilter );
		if ($ob = $ibres->GetNextElement ()) {
			$arFlds = $ob->GetFields ();
			$curID = $arFlds ["ID"];
			$dbRes = $ib->GetProperty ( 68, $curID, array (), array (
					"CODE" => "valu" 
			) );
			if ($arProps = $dbRes->Fetch ()) {
				$result = 1;
				$ib->SetPropertyValues ( $curID, 68, 1, 'VALU' );
			}
		}		
		return $result;
	}
}

class CArkaConstant {
	// get value by name
	static public function GetValueByName($nam) {
		$result = 0;
		
		$ib = new CIBlockElement ();
		$arFilter = array (
				"IBLOCK_ID" => 69,
				"NAME" => $nam 
		);
		
		$ibRes = $ib->GetList ( array (), $arFilter );
		if ($ob = $ibRes->GetNextElement ()) {
			$arFlds = $ob->GetFields ();
			$curID = $arFlds ["ID"];
			$dbRes = $ib->GetProperty ( 69, $curID, array (), array (
					"CODE" => "valu" 
			) );
			if ($arProps = $dbRes->Fetch ()) {
				$result = $arProps ["VALUE"];
			}
		}
		return $result;
	}
}

// класс для работы со списком "Компании Дополнительно"
class CArkaCompanyExtra {

	// проверить наличие для компании соотвествующего списка
	// по коду адуло
	// возвращает true or false
	public static function Exist($pkodadulo)
	{
		$res = false;
		$spkodadulo = trim(strval($pkodadulo));
	
		$arFilter = array(
				"IBLOCK_ID"=>82,
				"NAME"=>$spkodadulo
		);
		$ibRes = CIBlockElement::GetList( array(), $arFilter);
		if($ob = $ibRes->GetNextElement())
		{
			$res = true;			
		}
		else
		{
			$res = false;
		}		

		return $res;
	}
	
	// получить свойство по коду адуло и имени свойства
	public static function GetProperty($pkodadulo,$propname)
	{
		$res = 0;
		
		$spkodadulo = trim(strval($pkodadulo));
		
		$arFilter = array(
				"IBLOCK_ID"=>82,
				"NAME"=>$spkodadulo
		);
		
		$ibRes = CIBlockElement::GetList( array(), $arFilter);
		if($ob = $ibRes->GetNextElement())
		{
			$arFlds = $ob->GetFields();
			$curID = $arFlds["ID"];
			
			$dbRes = CIBlockElement::GetProperty( 82, $curID, array(), array("CODE"=>$propname));
			if($arProps = $dbRes->Fetch())
			{
				$res = $arProps["VALUE"];
			}
		}
		
		return $res;
	} 

	
	// сохранить свойство по коду адуло и имени свойства
	public static function SetProperty($pkodadulo,$propname,$pvalue)
	{
		$spkodadulo = trim(strval($pkodadulo));
		
		$arFilter = array(
				"IBLOCK_ID"=>82,
				"NAME"=>$spkodadulo
		);
		
		$ibRes = CIBlockElement::GetList( array(), $arFilter);
		if($ob = $ibRes->GetNextElement())
		{
			$arFlds = $ob->GetFields();
			$curID = $arFlds["ID"];
			
			$ar = array($propname => $pvalue);
			CIBlockElement::SetPropertyValuesEx($curID, 82, $ar);			
		}		
	}
	
	
	
	// создать элемент списка с параметрами в массиве
	// массив должен содержать элементы
	//  k, kwork, kmater, title, comid
	// возвращает
	//  код добавленного элемента
	//  false - if error
	public static function CreateElement($kodadulo,$arProps)
	{		
		$ib = new CIBlockElement();
		$ar = 	array(
				"IBLOCK_ID"=>82,
				"NAME"=>trim(strval($kodadulo)),
				"PROPERTY_VALUES"=>
					array(
						"k"=>$arProps["k"],
						"kwork"=>$arProps["kwork"],
						"kmater"=>$arProps["kmater"],
						"title"=>$arProps["title"],
						"comid"=>$arProps["comid"]
					)
			);
		
		$res = $ib->Add($ar);
		return $res;
	}

}



// changing data from bitrix and to bitrix
class CArkaObmen {
	
	// use folder /upload/arka
	
	// profile of ftp
	var $ftp_server = '';
	var $ftp_login = '';
	var $ftp_password = '';

	// ftp settings
	public function SetFtpProfile($par_ftp_server, $par_ftp_login, $par_ftp_password) {
		$ftp_login = $par_ftp_login;
		$ftp_password = $par_ftp_pasword;
		$ftp_server = $par_ftp_server;
	}

	// send company to ftp
	public function SendCompany($id_company) {
		$result = 0;

		$file = $_SERVER ["DOCUMENT_ROOT"] . '/arka/test.txt';

		// send file
		$connect = ftp_connect ( $ftp_server );
		if ($connect) {
			$login_result = ftp_login ( $connect, $ftp_login, $ftp_password );

			if (ftp_put ( $connect, 'test.txt', $file, FTP_BINARY )) {
				$result = 1;
			}

			ftp_quit ( $connect );
		} else {
			// return error
		}

		return $result;
	}
}

// методы для работы с сrm
class CArkaCrm { 

	// получить пользовательское поле для сделки
	private static function getUserFieldForDeal($dealid,$userfield)
	{
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields('CRM_DEAL',$dealid);
		return $arUF[$userfield]['VALUE'];
	}

	// получить пользовательское поле для компании
	private static function getUserFieldForCompany($companyid,$userfield)
	{
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields('CRM_COMPANY',$companyid);
		return $arUF[$userfield]['VALUE'];
	}
	
	// сохранить пользовательское поле для компании
	private static function setUserFieldForCompany($comid,$userfield,$valu){
		$GLOBALS['USER_FIELD_MANAGER']->Update('CRM_COMPANY',$comid,array($userfield=>$valu));
	}
	
	// сохранить пользовательское поле для сделки
	private static function setUserFieldForDeal($deal_id, $userfield, $valu)
	{
		$GLOBALS['USER_FIELD_MANAGER']->Update('CRM_DEAL',$deal_id,array($userfield=>$valu));
	}

    // получить количество позиций для сделки
    public static function GetQPosByDealID($dealid){
        return CArkaCrm::getUserFieldForDeal($dealid, 'UF_CRM_1397335828');
    }
	
	
	// получить наименование копия для компании
	public static function GetTitleForCompany($comid){
		return CArkaCrm::getUserFieldForCompany($comid,'UF_CRM_1389189714');		// new
	}
	
	// сохранить наименование копия для компании
	public static function SetTitleForCompany($comid, $valu){
		CArkaCrm::setUserFieldForCompany($comid, 'UF_CRM_1389189714', $valu);		// new
	}	
	
	// получить KodAdulo для компании
	public static function GetKodAduloForCompany($comid){
		return CArkaCrm::getUserFieldForCompany($comid,'UF_CRM_1389189750');		// new
	}

	// сохранить KodAdulo для компании
	public static function SetKodAduloForCompany($comid, $valu){
		CArkaCrm::setUserFieldForCompany($comid, 'UF_CRM_1389189750', $valu);		//new
	}
	

	// получить KWork для компании
	public static function GetKWorkForCompany($comid){
		return CArkaCompanyExtra::GetProperty($comid, 'kwork');		
	}
	
	// сохранить KWork для компании
	public static function SetKWorkForCompany($comid, $valu){
		CArkaCompanyExtra::SetProperty($comid, 'kwork', $valu);
	}

	// получить KMater для компании
	public static function GetKMaterForCompany($comid){
		return CArkaCompanyExtra::GetProperty($comid, 'kmater');		
	}
	
	// сохранить KMater для компании
	public static function SetKMaterForCompany($comid, $valu){
		CArkaCompanyExtra::SetProperty($comid, 'kmater', $valu);
	}

	// получить K для компании
	public static function GetKForCompany($comid){
		return CArkaCompanyExtra::GetProperty($comid, 'k');
	}
	
	// сохранить K для компании
	public static function SetKForCompany($comid, $valu){
		CArkaCompanyExtra::SetProperty($comid, 'k', $valu);
	}
	
	// получить группу области нумерации (наименование) по коду компании
	public static function GetRegionNumeratorForCompany($comid)
	{
		$reg = "";
		$reselem = CArkaCrm::getUserFieldForCompany($comid,'UF_CRM_1389863631');	// new
		$inam = CIBlockElement::GetByID($reselem);
		if($ar = $inam->GetNext()){
			$reg = trim($ar['NAME']);
		}
				
		return $reg;
	}
	
	// получить строку c методом информирования через _
	public static function GetMethodForCompany($comid)
	{
		$method = "";
		$res = CArkaCrm::getUserFieldForCompany($comid,'UF_CRM_1389864395');		// new
		if(is_array($res))
		{
			foreach($res as $elem)
			{
				$inam = CIBlockElement::GetByID(intval($elem));
				if($ar = $inam->GetNext())
				{
					$method .= trim($ar["NAME"]) . "_";
				}
			}				
		}
		if(strlen($method) > 0)
		{
			$method = substr($method, 0, strlen($method)-1);
		}
		
		return $method;
	}
	
	// получить строку с методом информирования через ,
	public static function GetMethodForCompanyPrintable($comid)
	{
		$ss = CArkaCrm::GetMethodForCompany($comid);
		$ss = str_replace('_', ', ', $ss);
		return $ss;
	}
	
	// получить phone_sms для компании
	public static function GetPhoneSmsForCompany($comid)
	{
		$sms = '';
		$res = CArkaCrm::getUserFieldForCompany($comid,'UF_CRM_1389909342');		// new
		$sms = $res;
		return $sms;
	}
	
	// получить "Телефон компании" (не встроенный) для перезвона
	public static function GetPhoneForCompany($comid)
	{
		$number = '';
		$number = CArkaCrm::getUserFieldForCompany($comid,'UF_CRM_1389909077');		
		return $number;
	}	
	
	// получить "Факс" компании (не встроенный) для отправки факса
	public static function GetFaxForCompany($comid)
	{
		$number = '';
		$number = CArkaCrm::getUserFieldForCompany($comid,'UF_CRM_1413456609');		
		return $number;
	}	
	
	
	// получить ид пользователя битрикс для компании
	public static function GetBitrixUserForCompany($comid)
	{
		return CArkaCrm::getUserFieldForCompany($comid,'UF_CRM_1407311242');		// new
	}
	
	// получить ид пользователя битрикс в виде user_519
	public static function GetBitrixUserForCompanyInPattern($comid)
	{
		$res = 'user_' . strval(CArkaCrm::GetBitrixUserForCompany($comid));
		return $res;
	}
	
	// получить work email компании
	public static function GetEmailByCompanyID($company_id) {
		$resu = '';

		$cfm = new CCrmFieldMulti ();
		$arFilter = array (
				'ENTITY_ID' => 'COMPANY',
				'TYPE_ID' => 'EMAIL',
				'COMPLEX_ID' => 'EMAIL_WORK',
				'ELEMENT_ID' => $company_id 
		);
		$arOrder = array ();

		$rescfm = $cfm->GetList ( $arOrder, $arFilter );
		if ($res1 = $rescfm->Fetch ()) {
			$resu = $res1 ['VALUE'];
		}

		return $resu;
	}

	// сохраняет work email по id компании
	// $em - email to write
	public static function SetEmailByCompanyID($company_id,$em){

	    //error_log('point 1',3,"konvita2.txt");
	    $cfm = new CCrmFieldMulti();
	    $ar = array(
		    'COMPLEX_ID' => 'EMAIL_WORK',
		    'VALUE_TYPE' => 'WORK',
		    'TYPE_ID' => 'EMAIL',	
		    'VALUE' => $em
	    );
	    //error_log('point 2',3,"konvita2.txt");
	    $resu = $cfm->Update(GetMultiIdByCompanyID($company_id),$ar);
	    if($resu){
		//error_log("hello1\n",3,"konvita2.txt");
	    }
	    else{
		//error_log("hello2\n",3,"konvita2.txt");
	    }

	}

	// получить ID записи в строке таблицы multi по ID компании
	public static function GetMultiIdByCompanyID($company_id){
	    $resu = 0;

	    $cfm = new CCrmFieldMulti ();
	    $arFilter = array (
			    'ENTITY_ID' => 'COMPANY',
			    'TYPE_ID' => 'EMAIL',
			    'COMPLEX_ID' => 'EMAIL_WORK',
			    'ELEMENT_ID' => $company_id
	    );
	    $arOrder = array ();

	    $rescfm = $cfm->GetList ( $arOrder, $arFilter );
	    if ($res1 = $rescfm->Fetch ()) {
		    $resu = $res1 ['ID'];
	    }

	    return $resu;
	}

	// получить массив с реквизитами компании по ID
	public static function GetCompanyFieldsByID($pid)
	{
		$arOrder = Array('DATE_CREATE' => 'DESC');
		$arFilter = array('ID' => $pid);
		$arMainFields = array();
		$arAddFields = array();

		$resList = CAllCrmCompany::GetList($arOrder,$arFilter);
		if($arRes = $resList->Fetch()){
			$arMainFields = $arRes;
		}

		$arFilter = array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $pid);
		$resmulti = CCrmFieldMulti::GetList(array(),$arFilter);
		while($res1 = $resmulti->Fetch())
		{
			$arMainFields[$res1["COMPLEX_ID"]] = $res1["VALUE"];
		}

		return $arMainFields;
	}
    
    // получить телефон компании по ID
    public static function GetCompanyPhoneByID($pid){
        $resar = CArkaCrm::GetCompanyFieldsByID($pid);
        return $resar["PHONE_WORK"];
    }
    
    
    // записать пользовательское поле "Тип документа" в сделку
    // $deal_id сделка
    // $type    1 предложение, 2 счет      
    public static function SetDealDocumentType($deal_id, $type){        
        $uf = 'UF_CRM_1392456515';
        if($type == 1){
            CArkaCrm::setUserFieldForDeal($deal_id, $uf, "26");                   
        }        
        elseif($type == 2){
            CArkaCrm::setUserFieldForDeal($deal_id, $uf, "27");
        }
    }
    

	// записывает инфо по компании в xml-файл
	// $idCli - код компании, файл будет называться Company123.xml
	// $arCli - массив с элементами которые будут записаны в xml файл
	public static function SaveCompanyXML($idCli,$arCli)
	{		
		$path = "/home/bitrix/www/upload/arka/"; // !!! временно (потом tmp убрать!)
		$fn = $path . 'company' . trim(strval($idCli)) . '.xml' ;
		$encoding = 'utf-8';

		$dom = new DOMDocument('1.0', $encoding);
		$root = $dom->appendChild($dom->createElement("company"));

		foreach($arCli as $ke => $vl){
			
			//error_log("ke is $ke\n",3,'konvita.log');
			
			if(is_array($vl))
			{
				$inner = $root->appendChild($dom->createElement($ke));
				$num = 1;
				foreach($vl as $keyItem => $valItem)
				{
					$valItems = strval($valItem);
					$localKey = 'key' . strval($num);
					$localElement = $dom->createElement($localKey,$valItems);
					
					$localAttr = $dom->createAttribute('key');
					$localAttr->value = $keyItem;
					$localElement->appendChild($localAttr);
					
					$locNode = $inner->appendChild($localElement);
									
				}	$num++;
			}
			else
			{
				$vls = strval($vl);
				
				// !!! надо разобраться надо ли это здесь и будут ли тут страны
				// отработать случаи когда значения берутся из списков (страны, города)
				if(($ke == 'UF_CRM_1366303548') || ($ke == 'UF_CRM_1372139506'))
				{
					$vln = intval($vl);
					$rs = CIBlockElement::GetByID($vln);
					if($arRs = $rs->GetNext()){
						$vls = $arRs['NAME'];
					}
					else{
						$vls = '';						
					}					
				}
								
				$root->appendChild($dom->createElement($ke,$vls));	
			}
			
		}
		$resSave = $dom->save($fn);
		//error_log("save result is $resSave",3,'konvita.log');
	}
	
	// проверить наличие товара в сделке
	// test if product ($productID) is in deal ($dealID) product list
	// returns 0 if no
	//	   1 if yes 		
	public static function IsProductInDeal($dealID,$productID){
		$res = 0;
	
		$ar = CCrmProductRow::LoadRows('D', $dealID);
		foreach($ar as $kk => $vv)
		{
			if($vv['PRODUCT_ID'] == $productID){
				$res = 1;
				break;
			}
		}
		return $res;
	}
	
	// получить ИД компании по ИД сделки
	public static function GetCompanyIDByDealID($deal_id)
	{
		$ar = CCrmDeal::GetByID($deal_id,false);
		return $ar['COMPANY_ID'];
	}
	
	// получить наименование компании по ID сделки
	public static function GetCompanyNameByDealID($deal_id)
	{
		$ar = CCrmDeal::GetByID($deal_id,false);	
		return $ar['COMPANY_TITLE'];
	}	
	
	
	// запустить БП crm сделки
	public static function StartCrmDealBP($template_id, $deal_id)
	{
		$deal = 'DEAL_'.$deal_id;
		$arErrorsTmp = array ();
		$wf = CBPDocument::StartWorkflow ( $template_id,
			array ( "crm",
				"CCrmDocumentDeal",
				$deal),
			array (	"Info" => "start from bp" ),
			$arErrorsTmp );		
	}
    
    // получить и перевести на русский статус сделки 
    public static function GetDealStage($deal_id)
    {
        $res = "";
        $ar = CCrmDeal::GetByID($deal_id);
        if(is_array($ar)){
            $rr = $ar["STAGE_ID"];
            $res = CArkaCrm::TranslateDealStage($rr);            
        }
        return $res;
    }    
    
    // получить оригинальный статус сделки 
    public static function GetDealStageOriginal($deal_id)
    {
        $res = "";
        $ar = CCrmDeal::GetByID($deal_id);
        if(is_array($ar)){
            $rr = $ar["STAGE_ID"];
            $res = $rr;            
        }
        return $res;
    }    
    
    // перевести статус сделки на человеческий язык
    public static function TranslateDealStage($rr){
        $res = "---";
        if($rr == "DETAILS"){
            $res = "Предварительная";
        }                        
        elseif($rr == "NEW"){
            $res = "Новая";
        }
        elseif($rr == "PROPOSAL"){
            $res = "Предложение";
        }
        elseif($rr == "NEGOTIATION"){
            $res = "Выставлен счет";
        }
        elseif($rr == "WON"){
            $res = "Сделка завершена успешно!";
        }
        elseif($rr == "LOSE"){
            $res = "Сделка завершена неудачно";
        }
        elseif($rr == "ON_HOLD"){
            $res = "Сделка приостановлена";
        }
        return $res;
    }
	
	// создать сделку
	// возвращает id новосозданной сделки
	public static function CreateCrmDeal($title,$summa = 0,$nds = 0,$company_id,$contact_id,$user_id,$comments = '',$arFiles)
	{
		/*
		error_log("test\n",3,'konvita2.txt');
		//define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/konvita.txt");
		//AddMessage2Log("Create deal");
		*/
		
		$arDeal = CArkaCrm::PrepareDealArray($title,$summa,$nds,$company_id,$contact_id,$user_id,$comments,$arFiles);
		
		/*
		//deb
		foreach($arDeal as $kk => $vv)
		{
			error_log("$kk === $vv\n",3,"konvita2.txt");	
		}
		*/
        
        //debug
        //AddMessage2Log(serialize($arDeal));
		
		$deal = new CCrmDeal();		
		$id = $deal->Add($arDeal);
		return $id;
	}
	
	// подготовить массив для создания новой сделки
	public static function PrepareDealArray($title,$summa,$qw,$qp,$company_id,$user_id,$comments,$arFiles)
	{
		/*
		параметры
		$title - наименование сделки
		$summa - сумма сделки
		$company_id - компания
		$user_id - пользователь (будет ответсвенным за сделку)
		$comments - комментарий (для письма - текст письма)
		$arFiles - массив файлов (не файлов, а их идентификаторов)
		$qw - количество окон
		$qp - количество позиций
		*/
		
		$ar = array();
		
		$ar['TITLE'] = $title;
		$ar['TYPE_ID'] = 'SALE';
		$ar['PROBABILITY'] = 100;
		$ar['OPPORTINITY'] = $summa; //стоимость сделки (а что я могу указать)
		$ar['CURRENCY_ID'] = 'UAH';
		$ar['ACCOUNT_CURRENCY_ID'] = 'UAH';
		$ar['STAGE_ID'] = 'NEW';
		$ar['ASSIGNED_BY'] = $user_id;
		$ar['ASSIGNED_BY_ID'] = $user_id;
		$ar['TAX_VALUE'] = $nds;
		$ar['ACCOUNT_CURRENCY_ID'] = 'UAH';
		$ar['OPPERTUNITY_ACCOUNT'] = $summa;
		$ar['TAX_VALUE_ACCOUNT'] = $nds;
		
		$arCompany = CArkaCrm::GetCompanyArrayByID($company_id);
		$ar = CArkaCrm::AppendToDealFromCompany($ar,$arCompany);
		
		$arContact = CArkaCrm::GetContactArrayByID($contact_id);
		$ar = CArkaCrm::AppendToDealFromContact($ar,$arContact);
		
		/*
		//dates
		$dt = new DateTime("now");
		$dt->setTime(0,0,0);
		$ar['BEGINDATE'] = $dt->getTimestamp();
		//$ar['BEGINDATE'] = date('Y-m-d H:i:s');
		$dt = $dt->Add(new DateInterval('P7D'));
		$ar['CLOSEDATE'] = $dt->getTimestamp();
		*/
		
		//user
		$ar = CArkaCrm::AppendToDealFromUser($ar,$user_id,'ASSIGNED');
		$ar = CArkaCrm::AppendToDealFromUser($ar,$user_id,'CREATED');
		$ar = CArkaCrm::AppendToDealFromUser($ar,$user_id,'MODIFY');
		
		$ar['OPENED'] = 'Y';
		$ar['CLOSED'] = 'N';
		$ar['COMMENTS'] = $comments;
		
		$ar['ADDITIONAL_INFO'] = null;
		$ar['LOCATION_ID'] = null;
		$ar['ORIGINATOR_ID'] = null;
		$ar['ORIGIN_ID'] = null;
		$ar['PRODUCT_ID'] = null;
		$ar['EVENT_ID'] = null;
		$ar['EVENT_DATE'] = null;
		$ar['EVENT_DESCRIPTION'] = null;
		
		$ar['ASSIGNED_BY'] = $user_id;
		$ar['CREATED_BY'] = $user_id;
		$ar['MODIFY_BY'] = $user_id;	
		
		$ar['UF_CRM_1392457261'] = "Y"; // Метод Информирования из Компании$
		$ar['UF_CRM_1392465232'] = '1759'; // Подробный статус Расчет с почты
		$ar['UF_CRM_1396631042'] = '3461386'; // Статус ПВХ Отсутствует
		
		$ar['UF_CRM_1397335755'] = $qw; // количество окон ПВХ
		$ar['UF_CRM_1397335828'] = $qp; // кво позиций окон ПВХ

		foreach($arFiles as $kk => $vv)
		{
			$ar['UF_CRM_1407847840'][] = $vv; //файлы
		}
		
		$ar['UF_CRM_1392456515'] = 26; // предложение
        
        $ar['UF_CRM_1407589534'] = "N"; // Метод Информирования из Компании$
		
				
		return $ar;		
	}
	
	// добавить к сделке поля по user - Assigned, Created, Modify
	//  $deal - массив с полями сделки
	//  $user_id
	//  $type - тип поля: 'MODIFY' 'ASSIGNED' 'CREATED'  
	private static function AppendToDealFromUser($deal,$user_id,$type)
	{
		$rsUser = CUser::GetbyID($user_id);
		$arUser = $rsUser->Fetch();
		
		foreach($arUser as $kk => $vv)
		{
			if($kk == 'ID'){
				$pp = $type . '_BY_ID';
				$deal[$pp] = $vv; 
			}
			elseif($kk == 'LOGIN'){
				$pp = $type . '_BY_LOGIN';
				$deal[$pp] = $vv; 
			}
			elseif($kk == 'NAME'){
				$pp = $type . '_BY_NAME';
				$deal[$pp] = $vv; 
			}
			elseif($kk == 'LAST_NAME'){
				$pp = $type . '_BY_LAST_NAME';
				$deal[$pp] = $vv; 
			}
			elseif($kk == 'SECOND_NAME'){
				$pp = $type . '_BY_SECOND_NAME';
				$deal[$pp] = $vv; 
			}		
		}
		
		return $deal;
	}
	
	// получить массив свойств компании по id
	private static function GetCompanyArrayByID($id)
	{
		$ar = CCrmCompany::GetByID($id);
		return $ar;
	}
	
	

	// получить массив свойств контакта по ID
	private static function GetContactArrayByID($id)
	{
		$ar = CCrmContact::GetbyID($id);
		return $ar;
	}
	
    
    // получить количество предложений в сделке
    public static function CountPropsInDeal($deal_id){
        $arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields('CRM_DEAL',$deal_id);
        $arFL = $arUF['UF_CRM_1389787932'];
        $res =  count($arFL['VALUE']);
        return $res;
    } 
    
    // получить количество счетов в сделке
    public static function CountInvoicesInDeal($deal_id){
        $arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields('CRM_DEAL',$deal_id);
        $arFL = $arUF['UF_CRM_1389787984'];
        $res =  count($arFL['VALUE']);
        return $res;
    }
    
    // получить суммарно количество счетов и предложений в сделке
    public static function CountPropsAndInvoicesInDeal($deal_id){
        $props = CArkaCrm::CountPropsInDeal($deal_id);
        $invoices = CArkaCrm::CountInvoicesInDeal($deal_id);
        return $props + $invoices;
    }
    
    
    // определить стадию сделки $deal_id по наличию файлов
    // возврат
    // 0 - не менять стадию
    // 1 - предложение есть, счета нет
    // 2 - счет есть  
    public static function GetDealStageByFiles($deal_id){
        $res = 0;
        
        $isProposal = (CArkaCrm::CountPropsInDeal($deal_id) > 0);
        $isInvoice = (CArkaCrm::CountInvoicesInDeal($deal_id) > 0);
        
        if($isProposal && !$isInvoice){
            $res = 1;
        }        
        elseif($isInvoice){
            $res = 2;
        }       
        
        return $res;
    } 
    
    // определить стадию сделки $deal_id по последнему присоединенному файлу
    // 0 - нет
    // 1 - предложение
    // 2 - счет
    public static function GetDealStageByLastFile($deal_id){
        $res = 0;
        
        $cod = CArkaCrm::GetLastFileTypeByDealID($deal_id);
        if($cod == 'scet'){
            $res = 2;
        }
        if($cod == 'pred'){
            $res = 1;
        }
        
        return $res;        
    } 
    
    
    // установить "Тип документа" сделки $deal_id по наличию файлов
    public static function SetDocumentTypeByFiles($deal_id){  
        $res = 0;
        $isProposal = (CArkaCrm::CountPropsInDeal($deal_id) > 0);
        $isInvoice = (CArkaCrm::CountInvoicesInDeal($deal_id) > 0);
        
        if($isProposal && !$isInvoice){
            $res = 1;
        }        
        elseif($isInvoice){
            $res = 2;
        }
        
        if($res == 1 || $res == 2)
            CArkaCrm::SetDealDocumentType($deal_id,$res);
    }   
    

	//добавить в массив некоторые элементы из компании
        //возвращает расширенный массив
        private static function AppendToDealFromCompany($arDeal,$arCompany)
        {
            foreach($arCompany as $kk => $vv)
            {
                if($kk == 'ID') {
                    $arDeal['COMPANY_ID'] = $vv;                    
                }
                elseif ($kk == 'COMPANY_TYPE') {
                    $arDeal['COMPANY_TYPE'] = $vv;   
                }
                elseif($kk == 'TITLE'){
                    $arDeal['COMPANY_TITLE'] = $vv;
                }
                elseif($kk == 'LOGO'){
                    $arDeal['COMPANY_LOGO'] = '';
                }
                elseif($kk == 'ADDRESS'){
                    $arDeal['COMPANY_ADDRESS'] = $vv;
                }
                elseif($kk == 'ADDRESS_LEGAL'){
                    $arDeal['COMPANY_ADDRESS_LEGAL'] = $vv;
                }
                elseif($kk == 'BANKING_DETAIL'){
                    $arDeal['COMPANY_BANKING_DETAIL'] = $vv;
                }
                elseif($kk == 'INDUSTRY'){
                    $arDeal['COMPANY_INDUSTRY'] = $vv;
                }
                elseif($kk == 'REVENUE'){
                    $arDeal['COMPANY_REVENUE'] = $vv;
                }
                elseif ($kk == 'CURRENCY_ID') {
                    $arDeal['COMPANY_CURRENCY_ID'] = $vv;
                }
                elseif($kk == 'EMPLOYEES'){
                    $arDeal['COMPANY_EMPLOYEES'] = $vv;                    
                }                
            }
            return $arDeal;    
        }
        


	//добавить в массив некоторые элементы из контакта
        //возвращает расширенный массив
        private static function AppendToDealFromContact($arDeal, $arContact)
        {
            foreach($arContact as $kk => $vv){
                if($kk == 'ID'){
                    $arDeal['CONTACT_ID'] = $vv;
                }        
                elseif($kk == 'POST'){
                    $arDeal['CONTACT_POST'] = $vv;
                }
                elseif($kk == 'ADDRESS'){
                    $arDeal['CONTACT_ADDRESS'] = $vv;
                }
                elseif($kk == 'NAME'){
                    $arDeal['CONTACT_NAME'] = $vv;
                }
                elseif($kk == 'SECOND_NAME'){
                    $arDeal['CONTACT_SECOND_NAME'] = $vv;
                }
                elseif($kk == 'LAST_NAME'){
                    $arDeal['CONTACT_LAST_NAME'] = $vv;
                }
                elseif($kk == 'FULL_NAME'){
                    $arDeal['CONTACT_FULL_NAME'] = $vv;                            
                }
                elseif($kk == 'PHOTO'){
                    $arDeal['CONTACT_PHOTO'] = $vv;
                }
                elseif($kk == 'TYPE_ID'){
                    $arDeal['CONTACT_TYPE_ID'] = $vv;                    
                }
                elseif($kk == 'SOURCE_ID'){
                    $arDeal['CONTACT_SOURCE_ID'] = $vv;
                }                
            }         
            return $arDeal;
        }

	
	// получить массив ИМЕН файлов расчетов присоединенных к сделке $deal_id
    public static function GetSentFilesNames($deal_id)
    {
        $arResult = array();
        
        $arIDs = CArkaCrm::GetSentFilesIDs($deal_id);
        foreach($arIDs as $vv){
            $arFileInfo = CFile::MakeFileArray($vv);
            if(is_array($arFileInfo)){
                $arResult[] = $arFileInfo['name'];
            }
        }
        
        return $arResult;
    } 
    
    //получить имя файла по его ID
    public static function GetFileNameByID($file_id){
        $res = "";
        
        $arFileInfo = CFile::MakeFileArray($file_id);
        if(is_array($arFileInfo)){
            $res = $arFileInfo['name']; 
        }
        
        return $res;
    }
    
    //получить дату модификации файла в UNIX формате
    public static function GetFileDateByID($file_id){
        $res = 0;
        
        $arFileInfo = CFile::MakeFileArray($file_id);
        if(is_array($arFileInfo)){
            $tmp_name = $arFileInfo['tmp_name'];
            $res = filemtime($tmp_name); 
        }
        
        return $res;
    } 
    
    //получить имя файла из списка присоединенных с последней датой изменения
    public static function GetLastFileNameByDealID($deal_id){
        $res = "";
        
        $maxdata = 0;
        $maxfid = 0;
        
        $arIDs = CArkaCrm::GetSentFilesIDs($deal_id);
        foreach($arIDs as $fid){
            $dat = CArkaCrm::GetFileDateByID($fid);
            if($dat > $maxdata){
                $maxdata = $dat;
                $maxfid = $fid;
            }
        }
        
        if($maxfid <> 0){
            $res = CArkaCrm::GetFileNameByID($maxfid);
        }
        
        return $res;
    }
    
    //получить тип последнего присоединенного файла для правильной установки стадии
    //возврщает scet или pred
    public static function GetLastFileTypeByDealID($deal_id){
        $res = 0;
        $rr = CArkaCrm::GetLastFileNameByDealID($deal_id);
        if($rr <> ''){
            $res = substr($rr,0,4);
        }        
        return $res;
    }
    	

	//получить массив списка ID файлов расчетов присоединенных
	//к сделке $deal_id
	public static function GetSentFilesIDs($deal_id)
	{
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields('CRM_DEAL',$deal_id);		
		$resultAr = $arUF['UF_CRM_1416388897']['VALUE'];		
		return $resultAr;
	}	
	
	// добавить ID файла ($valu) (предложения или счета) в
	// список отправленных файлов для сделки $deal_id
	// дублей не пишем
	public static function SetSentFileID($deal_id, $valu)
	{
		$arOld = CArkaCrm::GetSentFilesIDs($deal_id);
		// test if the same is in array 
		if(!in_array($valu,$arOld))
		{ 
			$arOld[] = $valu;
			CArkaCrm::setUserFieldForDeal($deal_id, 'UF_CRM_1416388897', $arOld);
		}
	}
	
	// проверить отправлялся ли файл сделки
    // тестирование будет производиться по именам файлов, а не по их IDs
	public static function IsSentFileInDeal($deal_id,$file_id)
	{
		$res = false;
        
        // получить имя проверяемого файла
        $file_name = CArkaCrm::GetFileNameByID($file_id); 
        
		$arSentNames = CArkaCrm::GetSentFilesNames($deal_id);
		if(in_array($file_name,$arSentNames))
		{
			$res = true;	
		}
		return $res;
	}
           
	// отправить все присоединенные НЕОТПРАВЛЕННЫЕ файлы предложений (или счетов)
	// в отдельных письмах
    // возвращает количество отправленных файлов 
	public static function SendUnsentEmailsByDealID($deal_id, $doctype='pred')
	{
        $res = 0; 	       
       
		$arFiles = array();
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields('CRM_DEAL',$deal_id);
		
		if($doctype == 'pred')
		{
			$arFl = $arUF['UF_CRM_1389787932']; // это поле "Предложения для КП" 	
		}
		else
		{
			$arFl = $arUF['UF_CRM_1389787984']; // это поле "Счет для КП" 	
		}		
		
		foreach($arFl['VALUE'] as $k => $v)
		{
            
            //debug
            //echo "v is $v"."<br/>";                                            		      
          
			//проверить что файл еще не отправлен
		    if(!CArkaCrm::IsSentFileInDeal($deal_id,$v))
            {
                //debug
                //echo "not sent <br/>";
                CArkaCrm::SendEmailByDealID($deal_id, $doctype, $k);
                CArkaCrm::SetSentFileID($deal_id, $v);
                $res++;                         
		    }			
		}			
        
        return $res; 
	}	
    
    // создать текст смс 
    // номер сделки + имя файла 
    public static function CreateSmsTextByDealIDAndFileName($dealid, $fn){
    
        $res = 'result';
        
        // define pred or scet
        // $type can be 'pred' or 'scet'
        $type = substr($fn,0,4);
        $ar = array();
        
        if($type == 'pred'){
            // get $ndoc
            $rr = preg_match('/predND\(\w+\)_/', $fn, $ar);
            if($rr):
                $rrs = $ar[0];
                $rrs = str_replace('predND(', '', $rrs);
                $rrs = str_replace(')_', '', $rrs);
                $ndoc = $rrs;
            else:
                $ndoc = "--";
            endif;    
            
            // get $summ        
            $rr = preg_match('/_ObSumSoSkid\(\w+\.\w+\)/', $fn, $ar);
            if($rr):
                $rrs = $ar[0];
                $rrs = str_replace('_ObSumSoSkid(', '', $rrs);
                $rrs = str_replace(')', '', $rrs);
                $sum = $rrs;
            else:
                $sum = "";
            endif;        
                    
            $res = sprintf('SMART %5d. Predl %6s. Summa so skidkoy: %sUAH. Horoshego dnya!', $dealid, $ndoc, $sum);
        }
        elseif($type == 'scet'){
            // get $ndoc
            $rr = preg_match('/scetND\(\w+\)_/', $fn, $ar);
            if($rr):
                $rrs = $ar[0];
                $rrs = str_replace('scetND(', '', $rrs);
                $rrs = str_replace(')_', '', $rrs);
                $ndoc = $rrs;
            else:
                $ndoc = "--";
            endif;    
            
            // get $summ        
            $rr = preg_match('/_ObSumSoSkid\(\w+\.\w+\)/', $fn, $ar);
            if($rr):
                $rrs = $ar[0];
                $rrs = str_replace('_ObSumSoSkid(', '', $rrs);
                $rrs = str_replace(')', '', $rrs);
                $sum = $rrs;
            else:
                $sum = "";
            endif;        
                    
            $res = sprintf('SMART %5d. Schet %6s. Summa so skidkoy: %sUAH. Spasibo, chto vyibrali nas!', $dealid, $ndoc, $sum);        
        }
        else{
            $res = '';
        }        
        
        return $res;
    }
    
    // отправить последний присоединенный файл (независимо  счет или предложение)
    public static function SendLastFileByDealID($deal_id){
        // get file type
        $ftype = CArkaCrm::GetLastFileTypeByDealID($deal_id);
        CArkaCrm::SendEmailByDealID($deal_id, $ftype, 99);
    }
	
	// отправить письмо на рабочую почту компании по сделке с присоединением файла из "Предложение для КП"
	// второй параметр определяет тип документа для отправки
	// 'pred' (default) or 'scet'
	// третий параметр определяет какой файл из списка надо отправить
	// если 99 - отправляет как раньше последний
    // одновременно выполняем отправку sms
	public static function SendEmailByDealID($deal_id, $doctype='pred', $index = 99)
	{
		$arFiles = array();
		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields('CRM_DEAL',$deal_id);
		
        $subj = "SMART " . strval($deal_id) . ". " ;
        
		if($doctype == 'pred')
		{
			$arFl = $arUF['UF_CRM_1389787932']; // это поле "Предложения для КП" 	
		}
		else
		{
			$arFl = $arUF['UF_CRM_1389787984']; // это поле "Счет для КП" 	
		}		
		
        // read to get last file properties to send
		foreach($arFl['VALUE'] as $k => $v)
		{
			$arFiles[] = CFile::GetFileArray($v);			
			//SendEmail("konvita_common@ukr.net",array($ar));
            
            // read sms number for company
            $comid = CArkaCrm::GetCompanyIDByDealID($deal_id);
            $smsno = CArkaCrm::GetPhoneSmsForCompany($comid);

            //+++ temporary commented
            
	        /* 	
            if(!empty($smsno)){ 
                // read file name to create sms message
                $a = CFile::GetFileArray($v);
                $a_fn = $a['FILE_NAME'];
            
                $smstext = CArkaCrm::CreateSmsTextByDealIDAndFileName($deal_id, $a_fn);
                CArkaSMS::SendSMS($smsno, $smstext);			    
            }           
            */
        	
		}
                
		$comid = CArkaCrm::GetCompanyIDByDealID($deal_id);		
		$email = CArkaCrm::GetEmailByCompanyID($comid);
        
        // сюда перенести подготовку инфо для sms
        $sms_info = array();        
        $smsno = CArkaCrm::GetPhoneSmsForCompany($comid);
        if(!empty($smsno)){
            $sms_info["comid"] = $comid;
            $sms_info["dealid"] = $deal_id;
            $sms_info["smsno"] = $smsno;
        }
		
 		CArkaCrm::SendEmail($email,$arFiles,$index,"ATTACH_FILES_BP",$subj, $sms_info);
        
	}

	// отправить письмо с массивом файлов
	// $Files_Send - что-типа 0 => 4611 (просто номер файла)
	private static function SendEmail($emails_to, $Files_Send, $index, 
        $type_post_template = "ATTACH_FILES_BP", $subj = "", $sms_info)
	{
	//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

		if(is_array($Files_Send)){
			
			$arCopyFiles = array();
			foreach($Files_Send as $key=>$file){ // тут будет что-типа 0 => 4611 (просто номер файла)
				$arFile = CFile::GetFileArray($file);
				$arCopyFiles[] = $arFile;
			}

			$arFieldmail = array(
				"NAME" => "user",
				"EMAIL_TO" => $emails_to,
			);
	
			if($index == 99) //if we need last file sending
			{	
				foreach($Files_Send as $key=>$file){      
					$arf = CFile::GetFileArray($file[ID]);	
                    $fn = $arf['FILE_NAME'];
					$arFieldmail['FILE'] = $arf['SRC'];			
				}
			}
			else		//if we need scecific file sending	
			{
				$file = $Files_Send[$index];
				$arf = CFile::GetFileArray($file[ID]);
                $fn = $arf['FILE_NAME'];	
				$arFieldmail['FILE'] = $arf['SRC'];			
			}
            
            // define subj
            $its_ok = true; 
            $dct = substr($fn, 0, 4);
            if($dct == 'pred'){
                $subj .= 'Предложение ';
            }
            else if($dct == 'scet'){
                $subj .= 'Счет ';
            }
            else{
                $its_ok = false;
            }
            
            $lpos = strpos($fn, '(');
            $rpos = strpos($fn, ')');
            
            if($lpos === FALSE || $rpos === FALSE){
                $its_ok = false;
            } 
            
            if($lpos >= $rpos){
                $its_ok = false;
            }
                        
            if($its_ok){
                $ss = substr($fn, $lpos+1, $rpos - $lpos - 1);
                $subj .= $ss . ".";                        
            }   
            
            $arFieldmail['SUBJECT'] = $subj;   
            
            // send sms
            if(!empty($sms_info)){
                
                $dealid = $sms_info["dealid"];
                $smsno = $sms_info["smsno"];
                
                $smstext = CArkaCrm::CreateSmsTextByDealIDAndFileName($dealid, $fn);
                CArkaSMS::SendSMS($smsno, $smstext);
                                
            }     
			
			//deb
			//AddMessage2Log('arFieldsmail is ' . serialize($arFieldmail));
		 
			$event = new CEvent;
			$event->Send($type_post_template, SITE_ID, $arFieldmail);
		}
	}
    
    
    // отправить письмо с извещением о запуске в работу заказа
    public static function SendProductionEmail($deal_id){
        
        $arFieldmail = array();
        
        $comid = CArkaCrm::GetCompanyIDByDealID($deal_id);		
		$email = CArkaCrm::GetEmailByCompanyID($comid);
        
        $arFieldmail['EMAIL_TO'] = $email; 
        $scet = CArkaCrm::GetDocNumberForDeal($deal_id);
        $arFieldmail['INVOICE'] = $scet;
        $sm = CArkaCrm::GetDealCostOriginal($deal_id);
        $arFieldmail['SUMMA'] = sprintf('%.2f',$sm);
        $arFieldmail['SUBJECT'] = 'SMART ' . $deal_id . '. Счет ' . $scet;        
        
        //deb
        //AddMessage2Log('arFieldsmail (production) is ' . serialize($arFieldmail));
        
        $event = new CEvent;
        $event->Send('BP_PRODUCTION', SITE_ID, $arFieldmail);
        
        // temporary to debug
        $arFieldmail['EMAIL_TO'] = 'callcenter@arka.ua';        
        $event = new CEvent;
        $event->Send('BP_PRODUCTION', SITE_ID, $arFieldmail);        
        
    }
    
    // отправить sms с извещением о запуске в работу заказа
    public static function SendProductionSms($deal_id){
        // прочитать и проверить sms номер
        $comid = CArkaCrm::GetCompanyIDByDealID($deal_id);
        $smsno = CArkaCrm::GetPhoneSmsForCompany($comid);
        if(!empty($smsno)){ 
            
            $deal_name = 'SMART ' . $deal_id;
            
            $invoice = CArkaCrm::GetDocNumberForDeal($deal_id);
            
            $sm = CArkaCrm::GetDealCostOriginal($deal_id);
            $summa = sprintf('%.2f',$sm);
            
            $smstext = $deal_name . '. ' . 'Podtverzhdaem zapusk v rabotu po schetu ' 
                . $invoice . ' na summu ' . $summa . 'UAH. Spasibo, chto vybrali nas!';
                
            //deb 
            //AddMessage2Log('smsno is ' . serialize($smsno) . '   smstext is ' . $smstext);   

            CArkaSMS::SendSMS($smsno, $smstext);
            
        }        
    }
    
    // отправить email и sms с информацией о запуске в работу
    public static function SendProduction($deal_id){
        CArkaCrm::SendProductionEmail($deal_id);
        CArkaCrm::SendProductionSms($deal_id);
    } 	
	
	// сформировать сообщение пользователю Битрикс (скорее всего - экстранета)
	// в виде
	// Смарт ХХХ. Стоимость заказа ХХХХХХ.ХХ грн.
	public static function CreatePortalMessage($dealid)
	{
		$res = "";
		
		$res .= 'SMART ' . strval($dealid) . '.';
		
		//get cost of the products
		$cost = CArkaCrm::GetDealCostOriginal($dealid);
		
		//form string
		$res .= ' Стоимость заказа ' . strval($cost) . ' грн.';
				
		return $res;
	}
	
	
	// сформировать сообщение пользователю о необходимости отправить факс
	// в виде
	// Смарт ХХХХХХ. Отправьте факс клиенту на номер ХХХХХХ.
	// неплохо бы ссылку...
	public static function CreateFaxMessage($dealid)
	{
		$res = "";
		
		$res .= 'SMART ' . strval($dealid) . '.';
		
		//get client name
		$cliname = CArkaCrm::GetCompanyNameByDealID($dealid);
		
		//get company id for deal
		$comid = CArkaCrm::GetCompanyIDByDealID($dealid);
		
		//get client phone number
		$clifax = CArkaCrm::GetFaxForCompany($comid);
		
		//form string
		$res .= ' Отправьте факс компании ' . $cliname . ' на номер ' . $clifax . '.';
        
        //get cost of the products
		$cost = CArkaCrm::GetDealCostOriginal($dealid);
		
		//form string
		$res .= ' Стоимость заказа ' . strval($cost) . ' грн.';
		
		return $res;
	}
	
	// сформировать сообщение пользователю о необходимости выполнить звонок
	// в виде
	// Смарт ХХХ. Выполните звонок клиенту ХХХХХХХХХ по телефону ХХХХХХХХХ. Стоимость его
	// заказа ХХХХХХ.ХХ грн.
	public static function CreateCallbackMessage($dealid)
	{
		$res = "";
		
		$res .= 'SMART ' . strval($dealid) . '.';
		
		//get client name
		$cliname = CArkaCrm::GetCompanyNameByDealID($dealid);
		
		//get company id for deal
		$comid = CArkaCrm::GetCompanyIDByDealID($dealid);
		
		//get client phone number
		$clitel = CArkaCrm::GetCompanyPhoneByID($comid);
		
		//form string
		$res .= ' Выполните звонок компании ' . $cliname . ' по телефону ' . $clitel . '.';
		
		//get cost of the products
		$cost = CArkaCrm::GetDealCostOriginal($dealid);
		
		//form string
		$res .= ' Стоимость заказа ' . strval($cost) . ' грн.';
		
		return $res;
	}
	
	// получить общую стоимость сделки
	public static function GetTotalDealCostForDeal($dealid)
	{
		$res = 0;
		$res = CArkaCrm::getUserFieldForDeal($dealid,'UF_CRM_1392800556');
		return $res;
	}
	
	// получить скидку1 для сделки 
	public static function GetDiscountForDeal($dealid)
	{
		$res = 0;
		$res = CArkaCrm::getUserFieldForDeal($dealid,'UF_CRM_1392800748');
		return $res;
	}
	
    // получить скидку2 для сделки 
	public static function GetDiscount2ForDeal($dealid)
	{
		$res = 0;
		$res = CArkaCrm::getUserFieldForDeal($dealid,'UF_CRM_1392800769');
		return $res;
	}
	
	// получить стоимость сделки (общая - скидка1 - скидка2)
	public static function GetDealCost($deal_id)
	{
		$res = 0;
	
		$sumtot = CArkaCrm::GetTotalDealCostForDeal($deal_id);
		$sumdiscount = CArkaCrm::GetDiscountForDeal($deal_id);
		$sumdiscount2 = CArkaCrm::GetDiscount2ForDeal($deal_id);
		$res = $sumtot - $sumdiscount - $sumdiscount2;	
	
		return $res;
	}
    
    // получить стоимость сделки напрямую
    public static function GetDealCostOriginal($deal_id){
        $res = 0;
        $ar = CCrmDeal::GetByID($deal_id);
        if(is_array($ar)){
            $res = $ar['OPPORTUNITY'];
        }
        return $res;   
    }
    
    // получить номер документа для сделки
	public static function GetDocNumberForDeal($dealid)
	{
		$res = 0;
		$res = CArkaCrm::getUserFieldForDeal($dealid,'UF_CRM_1407847876');
		return $res;
	}
	
}		

// callcenter methods
class CArkaCallCenter {

	// запустить отсчет оставшегося времени код БП (списки) 66
	public static function StartRestTime($task_name, $doc_id) {
		$arErrorsTmp = array ();
		$wf = CBPDocument::StartWorkflow ( 66, array (
				"iblock",
				"CIBlockDocument",
				$doc_id 
		), array (
				"TaskName" => $task_name 
		), $arErrorsTmp );
	}

	// создать задание на просчет
	private static function CreateCalcTask($name, $id_company, $id_deal, $id_smart, $qw, $qp, $source) {
		$title_company = '';

		// get company
		$ibc = new CAllCrmCompany ();
		$arFilter = array (
				'ID' => $id_company 
		);
		$res_comp = $ibc->GetList ( array (), $arFilter );
		if ($comp_info = $res_comp->Fetch ()) {
			$title_company = $comp_info ['TITLE'];
		}

		// save
		$ib = new CIBlockElement ();
		$arFlds = array (
				'NAME' => $name,
				'IBLOCK_ID' => 65,
				'ACTIVE' => 'Y' 
		);

		$prod_id = $ib->Add ( $arFlds );

		// define time to process
		$timeStart = CArkaConstant::GetValueByName ( 'win_start' );
		$timeProcess = CArkaConstant::GetValueByName ( 'win_pos' ) * $qp;

		// ex fields
		if ($prod_id) {

			$ib->SetPropertyValueCode ( $prod_id, "QW", $qw );
			$ib->SetPropertyValueCode ( $prod_id, "QP", $qp );

			$ib->SetPropertyValueCode ( $prod_id, "CompanyName", $title_company );
			$ib->SetPropertyValueCode ( $prod_id, "CompanyID", $id_company );
			$ib->SetPropertyValueCode ( $prod_id, "Source", $source );

			$ib->SetPropertyValueCode ( $prod_id, "NormaStart", $timeStart );
			$ib->SetPropertyValueCode ( $prod_id, "NormaProcess", $timeProcess );
		}

		return $prod_id;
	}

	// создать задание на просчет
	// параметры:
	// $name - имя элемента списка
	// $id_company - ID компании
	// $id_deal - код сделки (не использовать пока) 
	// $id_smart - номер смарта (не использовать пока) 
	// $qw - количество окон
	// $qp - количество позиций
	// $source - источник
	// $email - куда отправлять
	// $smart - номер смарта (число)
	// $method - метод информирования
	// $doc - документ (счет или предложение)
	// $primech - примечание
	// $bitrix_user - id (число) пользователя extranet который делает заявку
	//   (может быть 0 - тогда тянем из справочника компаний) 
	// $urgency - срочность
	// $produc - тип продукции
	// $taskname - название задания
	// $$tasktype - тип задачи (0 - просчет для окон, 1 - технический вопрос, 2 - обратный звонок)
	// $statue - статус
	// $phone_sms - телефон для отправки смс (если не указан, тянем из справочника компаний)
	// $arFiles - массив файлов с описаниями (используется в почте)
	// $title_company - имя компании
	public static function CreateCalcTaskEx($name, $id_company, $id_deal, $id_smart, $qw = 0, $qp = 0, $source, $email,
						$smart, $method, $doc, $primech = '', $bitrix_user = '', $urgency = '',
						$produc = '', $taskname = '', $tasktype = 0, $status = 'Ждет!', $phone_sms = '',
						$arFiles = array(), $title_company = '')
	{
		//$title_company = '';
		
		//error_log("id_company is $id_company\n",3,'kkk.log');
		

		//+++ can be deleted imho
		// get company
		/*
		$ibc = new CAllCrmCompany ();
		$arFilter = array (
				'ID' => $id_company 
		);
		$res_comp = $ibc->GetList ( array (), $arFilter );
		if ($comp_info = $res_comp->Fetch ()) {
			$title_company = $comp_info ['TITLE'];
		}
		*/
		//---
		
		//error_log("title_company is $title_company\n",3,'kkk.log');
		
		// save
		$ib = new CIBlockElement ();
		$arFlds = array (
				'NAME' => $name,
				'IBLOCK_ID' => 65,
				'ACTIVE' => 'Y' 
		);
		
		//error_log("start\n",3,'konvita.log');
		$prod_id = $ib->Add ( $arFlds );
		
		// крайний срок задачи
		$mins = CArkaConstant::GetValueByName ( 'win_start' ) +
			CArkaConstant::GetValueByName ( 'win_pos' ) * $qp +
			CArkaConstant::GetValueByName ( 'Reserv_Time' );
		$maxTime = CArkaMisc::CurrentTimePlusMinutes($mins);
		$maxTime = ConvertTimeStamp($maxTime,"FULL");
		//error_log("maxTime is $maxTime\n",3,'konvita.log');
		
		// define time to process
		if($tasktype == 0) { // просчет окон
		    $timeStart = CArkaConstant::GetValueByName ( 'win_start' );
		    $timeProcess = CArkaConstant::GetValueByName ( 'win_pos' ) * $qp;		    
		}
		elseif($tasktype == 1){ // технический вопрос
		    $timeStart = CArkaConstant::GetValueByName ( 'tech_start' );
		    $timeProcess = CArkaConstant::GetValueByName ( 'tech_solve' );		    
		}
		elseif($tasktype == 2){ // обратный звонок
 		    $timeStart = CArkaConstant::GetValueByName ( 'callback_start' );
		    $timeProcess = CArkaConstant::GetValueByName ( 'callback_solve' );		    
		}
		
		// $wtime перекрывает все расчитанное
		if($wtime > 0)
		{	    
		    $timeStart = $wtime;
		    $timeProcess = 0;    
		}
		
		// prepare text
		$arPrimech = array (
				"VALUE" => array (
						"TEXT" => $primech,
						"TYPE" => "text" 
				) 
		);
		
		// phone sms
		if($phone_sms == '')
		{
			$phone_sms = CArkaCrm::GetPhoneSmsForCompany($id_company);
		}
		
		// bitrix_user		
		if($bitrix_user == 0)
		{
			$bitrix_user = CArkaCrm::GetBitrixUserForCompany($id_company);
		}
		 		
		
		// method
		// если метод не указан, берем из компании
		if($method == '')
		{
			$method = CArkaCrm::GetMethodForCompany($id_company);
		}
		
		// urgency
		// не оставлять срочность пустой
		// если не указана, пишем стандартная
		if($urgency == '')
		{
			$urgency = 'Стандартная';
			//error_log("urgency is $urgency\n",3,'kkk2.log');
		}		

		// определить имя компании
		//$title_company = CArkaCrm::GetTitleForCompany($id_company);
		//$title_company = 'debug';
		
		if($title_company == '')
		{
			$title_company = CArkaCrm::GetTitleForCompany($id_company);	
		}
		
		//error_log("id_company is $id_company\n",3,'kkk2.log');
		//error_log("title is $title_company\n",3,'kkk2.log');
		
		// ex fields
		if ($prod_id) {
			
			$ib->SetPropertyValueCode ( $prod_id, "QW", $qw );
			$ib->SetPropertyValueCode ( $prod_id, "QP", $qp );
			
			$ib->SetPropertyValueCode ( $prod_id, "CompanyName", $title_company );
			$ib->SetPropertyValueCode ( $prod_id, "CompanyID", $id_company );
			$ib->SetPropertyValueCode ( $prod_id, "Source", $source );
			
			$ib->SetPropertyValueCode ( $prod_id, "NormaStart", $timeStart );
			$ib->SetPropertyValueCode ( $prod_id, "NormaProcess", $timeProcess );
			
			$ib->SetPropertyValueCode ( $prod_id, "Due_Date_Task", $maxTime );
			
			// ex props
			$ib->SetPropertyValueCode ( $prod_id, "email", $email );
			$ib->SetPropertyValueCode ( $prod_id, "smart", $smart );
			$ib->SetPropertyValueCode ( $prod_id, "method", $method );
			$ib->SetPropertyValueCode ( $prod_id, "doc", $doc );
			// $ib->SetPropertyValueCode($prod_id,"primech",$primech);
			//$ib->SetPropertyValueCode ( $prod_id, "bitrix_user", $arBitrixUser );
			$ib->SetPropertyValueCode ( $prod_id, "bitrix_user", $bitrix_user );
			$ib->SetPropertyValueCode ( $prod_id, "urgency", $urgency );
			$ib->SetPropertyValueCode ( $prod_id, "produc", $produc );
			$ib->SetPropertyValueCode ( $prod_id, "TaskName", $taskname );
			$ib->SetPropertyValueCode ( $prod_id, "STATUS_TASK", $status );
			$ib->SetPropertyValueCode ( $prod_id, "PHONE_SMS", $phone_sms );
			
			// text saving
			// $ib->SetPropertyValuesEx($prod_id,65,$ar);
			$ib->SetPropertyValueCode ( $prod_id, "primech", $arPrimech );
			
			// attached files (for mail)
			$ib->SetPropertyValueCode($prod_id,"FormFiles",$arFiles);
		}
		
		return $prod_id;
	}
	
	
	// получить название задания по теме письма
	public static function GetTypeOfSource($theme) {
		$res = '---';
		
		if (preg_match ( '/win_p\d+_w\d+/', $theme ) == 1) {
			$res = 'form';
		} elseif (preg_match ( '/win\s+[pw]\d+\s+[wp]\d+/', $theme ) == 1) {
			$res = 'email';
		}
		;
		
		return $res;
	}
}


// методы разные
class CArkaMisc{
	
	// транслитерация для utf-8
	function GetInTranslit($string) {
		$replace=array(
			"'"=>"",
			"`"=>"",
			"а"=>"a","А"=>"a",
			"б"=>"b","Б"=>"b",
			"в"=>"v","В"=>"v",
			"г"=>"g","Г"=>"g",
			"д"=>"d","Д"=>"d",
			"е"=>"e","Е"=>"e",
			"ж"=>"zh","Ж"=>"zh",
			"з"=>"z","З"=>"z",
			"и"=>"i","И"=>"i",
			"й"=>"y","Й"=>"y",
			"к"=>"k","К"=>"k",
			"л"=>"l","Л"=>"l",
			"м"=>"m","М"=>"m",
			"н"=>"n","Н"=>"n",
			"о"=>"o","О"=>"o",
			"п"=>"p","П"=>"p",
			"р"=>"r","Р"=>"r",
			"с"=>"s","С"=>"s",
			"т"=>"t","Т"=>"t",
			"у"=>"u","У"=>"u",
			"ф"=>"f","Ф"=>"f",
			"х"=>"h","Х"=>"h",
			"ц"=>"c","Ц"=>"c",
			"ч"=>"ch","Ч"=>"ch",
			"ш"=>"sh","Ш"=>"sh",
			"щ"=>"sch","Щ"=>"sch",
			"ъ"=>"","Ъ"=>"",
			"ы"=>"y","Ы"=>"y",
			"ь"=>"","Ь"=>"",
			"э"=>"e","Э"=>"e",
			"ю"=>"yu","Ю"=>"yu",
			"я"=>"ya","Я"=>"ya",
			"і"=>"i","І"=>"i",
			"ї"=>"yi","Ї"=>"yi",
			"є"=>"e","Є"=>"e"
		);
		return $str=iconv("UTF-8","UTF-8//IGNORE",strtr($string,$replace));
	}
	
	// добавить к времени количество минут и вернуть результат в unixtype
	// $basetime - unix time
	// $min - number of minutes
	public static function PlusMinutes($basetime,$min)
	{
		$result = $basetime;
		
		$arAdd = array('MI' => $min);
		$result = AddToTimeStamp($arAdd, $basetime);
	
		return $result;
	}
	
	// добавить к текущему времени количество минут и вернуть время в unixtime
	public static function CurrentTimePlusMinutes($mins)
	{
		$scurtime = date('d.m.y H:i');
		$curtime = MakeTimeStamp($scurtime,'DD:MM:YYYY HH:MI');				
		$result = CArkaMisc::PlusMinutes($curtime,$mins);
		return $result;
	}
	
	// получить разницу в минутах для двух дат
	// dat1 & dat2 - даты как строки
	public static function DateDiffInMinutes($dat1,$dat2)
	{
		$udat1 = MakeTimeStamp($dat1,'DD.MM.YYYY HH:MI');
		$udat2 = MakeTimeStamp($dat2,'DD.MM.YYYY HH:MI');
		
		$udiff = $udat2 - $udat1;
		return floor($udiff / 60);	
	}
	
	// получить наименование продукции по ID
	public static function GetNameByID($prod_id)
	{
		$res = '';
		
		$res = CIBlockElement::GetByID($prod_id);
		if($arRes = $res->GetNext())
		{
			$res = $arRes['NAME'];
		}		
		
		return $res;
	}
	
	
	// записать в список баллов запись
	// $sotrnam - имя сотрудника
	// $sotr - ид сотрудника (сьтроки или число)
	// $datbal - дата за которую ставится оценка
	// $bal - балл число
	// $notes - комментарий по оценке 
	public static function AddBal($sotrnam, $sotr, $datbal, $bal, $notes)
	{
		$el = new CIBlockElement();

		$fields = array(
			'IBLOCK_ID' =>48,
			'NAME' => $sotrnam,
			'ACTIVE' => 'Y'
		
		);
		
		$ID = $el->Add($fields);
		CIBlockElement::SetPropertyValueCode($ID, "DatBal", $datbal);
		CIBlockElement::SetPropertyValueCode($ID, "Bal", $bal);
		CIBlockElement::SetPropertyValueCode($ID, "Notes", $notes);
		CIBlockElement::SetPropertyValueCode($ID, "Sotr", $sotr);
	}
	
	

	// отправить почтовое сообщение с присоединенными файлами
	// $ELEMENT_ID - ID элемента БП
	// $name - наименование элемента БП
	// $Files_Send - массив (?) файлов для отправки (множ поле?)
	// $type_post_template - почтовый шаблон
	// $emails_to - адрес получателя
	// $arEx - массив с доп полями в виде $arEx["APPLICANT"] должны соответсвовать полям в шаблоне
	public static function SendEmailCounterR($ELEMENT_ID, $name, $Files_Send, $type_post_template = "ATTACH_FILES_BP", $emails_to, $arEx =array()){
	
	/* запуск з бп кодом:
	$rootActivity = $this->GetRootActivity(); 
	$Files_Send = $rootActivity->GetVariable("Files_Send"); 
	SendEmailCounter("{=Document:ID}", "{=Document:NAME}", $Files_Send);
	*/
	
	/*
	Файлы для отправки: Files_Send
	Файлы расчета: Files_Count
	*/
	  require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	  require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/include.php");
	  
	  if(is_array($Files_Send)){
	    
	    $arCopyFiles = array();
	
	    foreach($Files_Send as $key=>$file){
	      
	      $arFile = CFile::MakeFileArray($file);
	      $arCopyFiles[] = $arFile;
	    }
	    CIBlockElement::SetPropertyValueCode($ELEMENT_ID, "Files_Count", $arCopyFiles);
	    
	    $rsElements = CIBlockElement::GetList(array("sort"=>"asc"), array("ID" => $ELEMENT_ID), false, false, Array('ID', 'IBLOCK_ID'));
	
	    $arCopyFilesID = array();
	    while($obElement = $rsElements->GetNextElement()){
	      $arElement = $obElement->GetFields();
	      $arProp_Files_Count = $obElement->GetProperty("Files_Count");
	      
	      foreach($arProp_Files_Count['VALUE'] as $file) $arCopyFilesID[] = $file;
	    }
	
	    $arFieldmail = array(
	      "NAME" => $name,
	      "EMAIL_TO" => $emails_to,
	    );
	    
	    // добавить дополнительные поля  в массив
	    /*
	    foreach($arEx as $kk => $vv){
		$arFieldmail[$kk] = $vv;
	    }
	    */
	    
	    $itt = 1;    
	    foreach($arCopyFilesID as $file){
	      
	      $arFile = CFile::GetFileArray($file);
	      foreach($arCopyFiles as $fl){
		if(($fl['size'] == $arFile['FILE_SIZE']) && ($fl['name'] == $arFile['ORIGINAL_NAME'])){
		  $arFieldmail['FILE'.$itt] = $arFile['SRC'];
		  $itt++;        
		}
	      }
	    }
	    
	    error_log("almost end",3,"_test1.log");
		 
	    $event = new CEvent;
	    $event->Send($type_post_template, SITE_ID, $arFieldmail);
	  }
	  else{
	
	  }
	}
}

// методы для определения загрузки конструкторов
// under construction and may be cancelled
class CArkaProscetLoad{
	function AddLoad($nam,$sotr_id,$work_date,$total_work_time,$orders,$positions)
	{
		
		
	}
}


// sms
class SMSClient
{
	public $mode = 'https';
	protected $_server = 'speedsms.com.ua';
	protected $_script = '/cgi-bin/api2.0.cgi';
	public $error = false;
	protected $_last_response = array();
	protected $_data = array();
	protected $_sdata = array();
	protected $_status = array();
	public $msgs = array();
	public $status = array();
	private $_version = '2.0';

	public function __construct($login, $password)
	{
		$this->_login = $login;
		$this->_password = $password;
	}

	public function addSMS($from, $to, $message, $send_dt = 0, $flash = 0, $expired = 0)
	{
		$d = is_numeric($send_dt) ? $send_dt : strtotime($send_dt);
		$sr = array(	'from'=>$from,
						'to'=>$to,
						'message'=>$message,
						'ask_date'=>$send_dt?date(DATE_ISO8601, $d):0,
						'flash'=>$flash,
						'expired'=>$expired);
		if(is_array($to))
		$this->_sdata[] = $sr;
		else
		$this->_data[] = $sr;
	}

	public function addStatus($uid)
	{
		$this->_status[] = $uid;
	}

	public function getResponse()
	{
		return $this->_last_response;
	}

	public function send()
	{
		$xml_data = '<?xml version="1.0" encoding="utf-8" ?><package login="'.$this->_login.'" sig="'.sha1($this->_login.md5($this->_password)).'" classver="'.$this->_version.'"><alphanames />';
		if(count($this->_data)){
			$xml_data .= '<messages>';
			foreach($this->_data AS $val){
				$xml_data .= '<msg recipient="'.$val['to'].'" sender="'.$val['from'].'" date_beg="'.$val['ask_date'].'" type="'.($val['flash']?1:0).'" expired="'.(int)($val['expired']).'">'.$val['message'].'</msg>';
			}
			$xml_data .= '</messages>';
		}
		if(count($this->_sdata)){
			$xml_data .= '<sendings>';
			foreach($this->_sdata AS $val){
				$xml_data .= '<sending sender="'.$val['from'].'" date_beg="'.$val['ask_date'].'" type="'.($val['flash']?1:0).'" expired="'.(int)($val['expired']).'"><msg>'.$val['message'].'</msg><recipients>';
				foreach($val['to'] as $phn)
					$xml_data .= '<phone>'.$phn.'</phone>';

				$xml_data .= '</recipients></sending>';
			}
			$xml_data .= '</sendings>';
		}
		if(count($this->_status)){
			$xml_data .= '<status>';
			foreach($this->_status AS $val){
					$xml_data .= '<msg uid="'.$val.'"></msg>';
			}
			$xml_data .= '</status>';
		}
		$xml_data .= '</package>';
		$_response = new XML($this->sendToServer($xml_data));
		$_response = $_response->package;
		if($_response){
			$this->error = (string)$_response->error;
			$this->balance = (string)$_response->balance;
			if($_response->messages && $_response->messages->msg)
				foreach($_response->messages->msg as $val){
					$this->msgs[] = array('res'=>(string)$val,'uid'=>(string)$val ? 0 : $val->offsetGet('uid'));
				}

			if($_response->sendings && $_response->sendings->sending)
			foreach($_response->sendings->sending as $val){
				$phns = array();
				if($val->phone)
					foreach($val->phone as $phn)
						$phns[] = array('uid'=>$phn->offsetGet('uid'),'res'=>(string)$phn);
				$this->sendings[] = array('uid'=>$val->offsetGet('uid'),'phones'=>$phns);
			}

			if($_response->status && $_response->status->msg)
				foreach($_response->status->msg as $val){
					$this->status[] = array('res'=>(string)$val,'uid'=>(string)$val ? '' : $val->offsetGet('uid'));
				}

			if($_response->alphanames && $_response->alphanames->alphaname)
				foreach($_response->alphanames->alphaname as $val){
					$this->alphanames[] = (string)$val;
				}
		} else {
			$this->error = 500;
			return;
		}
		return $_response;
	}

	function sendToServer($xml_data) {
		$headers = array(
		    "POST ".$this->_script." HTTP/1.1",
		    "Host: ".$this->_server,
		    "Content-Type: text/xml; charset=utf-8",
		    "Content-length: " . strlen($xml_data)
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, strtolower($this->mode). '://' . $this->_server. $this->_script);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
		$data = curl_exec($ch);
		if (curl_errno($ch)) {
		    die("Error: " . curl_error($ch));
		} else {
		    curl_close($ch);
		    return $data;
		}
	}

}



// arka sms
class CArkaSMS
{
	public static function SendSMS($phone, $text)
	{
		$ttext = CArkaMisc::GetInTranslit($text);
		$sms = new SMSClient('380965468079','arkaportal2015');
		$sms->addSMS('arka.info',$phone,$ttext, 0, 0, 1);
		$sms->send();
		return $sms;
	}	
}

?>