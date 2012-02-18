<?php
/**
* CLASS xmlSerializer
* object to xml serialization and unserialization
* @auteur : johan <barbier_johan@hotmail.com>
* @version : 1
* @date : 2006/03/22
*
* free to use, modify, please just tell me if you make any changes :-)
*/
class xmlserialize {

	/**
	* private object oObj
	* the object we work on
	*/
	private $oObj = null;
	/**
	* private array of object oPropObj
	* objects needed by the main object, because some of its properties are objects
	*/
	private $oPropObj = array ();
	/**
	* private array aProps
	* the PUBLIC properties of the object
	*/
	private $aProps = array ();
	/**
	* private string xml
	* the xml serailization of the object
	*/
	private $xml = '';
	/**
	* public string node
	* a fragment of the xml string
	*/
	public $node = '';

	/**
	* public function __construct
	* constructor
	* @Param (object) $obj : the object we want to serialize/unserialize
	* @Param (array) $oPropObj : array of objects needed by the main object
	*/
	public function __construct ($obj, array $oPropObj = array ()) {
		if (!is_object ($obj)) {
			trigger_error ('The first argument given is not an object', E_USER_ERROR);
		} else {
			$this -> oObj = $obj;
		}
		if (!empty ($oPropObj)) {
			foreach ($oPropObj as $clef => $oVal) {
		    	if (is_object ($oVal)) {
				  $this -> oPropObj[$clef]['object'] = $oVal;
				  $this -> oPropObj[$clef]['class'] = get_class ($oVal);
				}
			}
		}
	}

	/**
	* public function getProps ()
	* method used to get the public properties of the object
	*/
	public function getProps () {
		$this -> aProps = get_object_vars ($this -> oObj);
	}

	/**
	* private function recVarsToXml
	* method used to serialize the object, recursive
	* @Params (DomDocument) & docXml : the DomDocument object
	* @Params (DomElement) & xml : the current DomElement object
	* @Params (array) & aProps : the array of properties we work on recursively
	*/
	private function recVarsToXml (& $docXml, & $xml, & $aProps) {
		foreach ($aProps as $clef => $val) {
			if (empty ($clef) || is_numeric ($clef)) {
				$clef = '_'.$clef;
			}
			$domClef = $docXml -> createElement ((string)$clef);
			$domClef = $xml -> appendChild ($domClef);
			if (is_scalar ($val)) {
				$valClef = $docXml -> createTextNode ((string)$val);
				$valClef = $domClef -> appendChild ($valClef);
			} else {
				if (is_array ($val)) {
					$this -> recVarsToXml ($docXml, $domClef, $val);
				}
				if (is_object ($val)) {
					$oXmlSerialize = new self ($val);
					$oXmlSerialize -> getProps ();
					$oXmlSerialize -> varsToXml ();
					$objClef = $docXml -> importNode ($oXmlSerialize -> node, true);
					$objClef = $domClef -> appendChild ($objClef);
				}
			}
		}
	}

	/**
	* public function varsToXml
	* method used to serialize the object
	* @Return (string) $xml : the xml string of the serialized object
	*/
	public function varsToXml () {
		$docXml = new DOMDocument ('1.0', 'utf-8');
		$xml = $docXml -> createElement ('object_'.get_class ($this -> oObj));
		$xml = $docXml -> appendChild ($xml);
		$this -> recVarsToXml ($docXml, $xml, $this -> aProps);
		$this -> node = $xml;
		return $this -> xml = $docXml -> saveXML ();
	}

	/**
	* private function recXmlToVars
	* method used to unserialize the object, recursive
	* @Param (array) aProps : the array we work on recursively
	*/
	private function recXmlToVars ($aProps) {
		foreach ($aProps as $clef => $val) {
			$cpt = count ($val);
			if ($cpt > 0) {
				foreach ($val as $k => $v) {
					$cpt2 = count ($v);
					if ($cpt2 > 0) {
					  	if (substr ($k, 0, 7) === 'object_') {
						    foreach ($this -> oPropObj as $kObj => $vObj) {
						        if ($this -> oPropObj[$kObj]['class'] === substr ($k, 7)) {
                                    $oXmlSerializer = new self ($this -> oPropObj[$kObj]['object']);
                                    $oXmlSerializer -> getProps ();
                                    $sXml = $oXmlSerializer -> varsToXml ();
                                    $oXmlSerializer -> xmlToVars ($sXml);
                                    $this -> oObj -> {$clef}[substr ($k, 7)] = $oXmlSerializer -> getObj ();
                                }
							}
						} else {
							$this -> recXmlToVars ($v);
						}
					} else {
						if ($k{0} === '_') {
							$k = substr ($k, 1, strlen($k) - 1);
						}
						$this -> oObj -> {$clef}[$k] = current ($v);
					}
				}
			} elseif (!empty ($val)) {
				$this -> oObj -> $clef = current ($val);
			}
		}
	}

	/**
	* public function xmlToVars
	* method used to unserialize the object
	* @Param (string) xml : optional xml string (an already serialized object)
	*/
	public function xmlToVars ($xml = '') {
		if (empty ($xml)) {
			$xml = simplexml_load_string ($this -> xml);
		} else {
			$xml = simplexml_load_string ($xml);
		}
		$this -> recXmlToVars ($xml);
	}

	/**
	* public function getObj
	* method used to get the unserialized object
	* @Return (object) oObj : the unserialized object
	*/
	public function getObj () {
		return $this -> oObj;
	}
	
	/**
	* public method __toString
	* displays either the generated xml, or the object's properties to be serialized if the xml has not yet been 		generated
	* This method requires the XSL extension to be set i
	* Special thanks to Erwy, developpez.com XML forum administrator, who debugged my XSL :-), and to Tiscars, who tried to help too!
	* @Returns (string) sString 
	*/
	public function __toString () {
		$sString = '';
	  	if (isset ($this -> xml) && !empty ($this -> xml)) {
			if (class_exists ('XSLTProcessor')) {
		  		$sString = '<br /><br /><span style="background-color: #ffcc33;">XML DISPLAY</span><br />';
				$sXsl = <<<XSL
<?xml version ="1.0" encoding ="utf-8" ?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	extension-element-prefixes="php">
	<xsl:output method="xml" indent="yes" encoding="utf-8" />
	<xsl:namespace-alias stylesheet-prefix="php" result-prefix="xsl" />
   <xsl:template match="/">
      <ul>
         <xsl:apply-templates select="*"/>
      </ul>
   </xsl:template>
   <xsl:template match="*">
      <li>
         <xsl:value-of select="local-name ()"/><xsl:apply-templates select="text()"/>         
      <xsl:if test="*"><ul>
            <xsl:apply-templates select="*"/>
         </ul></xsl:if>
      </li>
   </xsl:template>
   <xsl:template match="text()">
      <xsl:value-of select="concat('=&gt;',.)"/>
   </xsl:template>
</xsl:stylesheet> 
XSL;
			  	$xsl = new XSLTProcessor();
			  	$xsl->importStyleSheet(DOMDocument::loadXML($sXsl));
				$sString .= $xsl->transformToXML(DOMDocument::loadXML($this -> xml));
			} else {
				$sString = '<br /><br /><span style="background-color: #ffcc33;">XSL EXTENSION NOT SET IN YOUR PHP.INI</span><br /><br />';
			}
		} else {
			$sString = '<br /><br /><span style="background-color: #ffcc33;">OBJECT PROPERTIES DISPLAY</span><br /><br />';
			$sString .= '<pre>'.var_export ($this -> aProps, true).'</pre>';
		}
		return $sString;
	}

}
?>