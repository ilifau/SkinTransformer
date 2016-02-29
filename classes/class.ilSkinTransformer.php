<?php

/* Copyright (c) 1998-2011 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
* Skin Transformer class
* 
* This class (or the child classes) do an actual transformation
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
* @version $Id$
*/
class ilSkinTransformer
{
	/**
	 * UIHookGUI object
	 * 
	 * Has to be static because calls from the XSL stylesheet can only be static.
	 * @var object
	 */
	static $gui_object;
	
	
	/**
	 * Plugin object
	 * 
	 * Has to be static because calls from the XSL stylesheet can only be static.
	 * @var object
	 */
	static $plugin_object;

	
	/**
	 * Cache for xsl processor objects
	 * 
	 * @var array	xsl file name => processor object
	 */
	static $kept_processors = array();
	
	
	/**
	 * constructor
	 * 
	 * sets the UIHookGUI gui object
	 */
	public function __construct($a_gui_obj)
	{
		self::$gui_object = $a_gui_obj;
		self::$plugin_object = self::$gui_object->getPluginObject();
	}
	

	/**
	 * Apply an xsl transformation
	 * 
	 * @param 	string		code to be transformed
	 * @param 	array		transformation definition, see transformations.xml
	 * 
	 * @return	string		transformed code
	 */
	public function transform($a_code, $a_trans)
	{
		// Return empty string if 'with' is not specified
		// (child classes may overwrite this behavoir)
		if (!$a_trans['with'])
		{
			return '';
		}
		// Return the file contents directly if 'with' is not an xsl file
		// (child classes may overwrite this behavior)
		elseif(pathinfo($a_trans['with'], PATHINFO_EXTENSION) != 'xsl')
		{
			$html = @file_get_contents(self::$gui_object->getSkinDirectory()
											.'/'.$a_trans['with']);			
			if ($html !== false)
			{
				return $html;
			}
			else
			{
				return '';
			}
		}
		
		// Get the processor with loaded XSL stylesheet
		if (!$xslt = $this->getXSLProcessor($a_trans))
		{
			// output an error message if xsl is not found
			echo "XSL not loaded: ";
			print_r($a_trans);
			return $a_code;
		}
				
		// Provide the function parameters directly
		$xslt->setParameter('', $a_trans);

		// Get the code to be transformed as a DOM object
		// Use HTML loading for fault tolerance (doesn't need to be well-formed)
		// Apply handling of utf-8 due to bugs in loadHTML()
		// Note: <html> and <body> elements will automatically be added!
		
		$dom_doc = new DOMDocument('1.0', 'UTF-8');
		if ($a_trans['utf8fix'] == 'entities')
		{
			@$dom_doc->loadHTML(mb_convert_encoding($a_code, 'HTML-ENTITIES', "UTF-8")); 
			
		}
		elseif ($a_trans['utf8fix'] == 'prefix')
		{
			@$dom_doc->loadHTML('<?xml encoding="UTF-8"?'.'>'. $a_code); 
			
		}
		else
		{
			@$dom_doc->loadHTML($a_code);
	    }
		        		
		if ($a_trans['debug'] == 'dom')
		{
			return $dom_doc->saveHTML();
		}
		
		// Process and supress warnings (e.g. due to '&' in links)
		return $xslt->transformToXML($dom_doc);
	}
	
	
	/**
	 * Get the XSL processor for a transformation
	 * 
	 * Optionally cache the transformer, if $a_trans['keep'] == 'true'
	 * 
	 * @param 	array		transformation definition, see transformations.xml
	 * @return	object		transformer_object
	 */
	protected function getXSLProcessor($a_trans)
	{
		$xsl_file = self::$gui_object->getSkinDirectory().'/'.$a_trans['with'];
		
		if (isset(self::$kept_processors[$xsl_file]))
		{
			// take a cached processor, if exist
			return self::$kept_processors[$xsl_file];	
		}
		elseif (!$xsl_code = file_get_contents($xsl_file))
		{
			// stylesheet not found
			return null;
		}
		else
		{	// load the xsl file	
			if (!$xsl_doc = @DOMDocument::loadXML($xsl_code))
			{
				return null;
			}
			// set the URI to allow document() within the XSL file
			$xsl_doc->documentURI = $xsl_file;
			
			// create a new processor
			$xslt = new XSLTProcessor();
			$xslt->registerPhpFunctions();
			$xslt->importStyleSheet($xsl_doc);
			
			// optionally keep the processor objects for further transformations
			if ($a_trans['keep'] == 'true')
			{
				self::$kept_processors[$xsl_file] = $xslt;
			}
			return $xslt;
		}
	}
	
	
	/** 
	 * Get the skin directory
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getSkinDirectory')
	 *
	 * @return	string	skin directory (relative path)
	 */
	static function getSkinDirectory()
	{
		return (string) self::$gui_object->getSkinDirectory();
	}
	
	/** 
	 * Get an ILIAS setting
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getSetting','setting_name')
	 *
	 * @param 	string	setting name
	 * @return	string	param value
	 */
	static function getSetting($a_name)
	{
		global $ilSetting;
		
		return (string) $ilSetting->get($a_name);
	}

	/** 
	 * Get a localized text from the global language
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getTxt','lang_var')
	 *
	 * @param 	string	lang var
	 * @return	string	text value
	 */
	static function getTxt($a_name)
	{
		global $lng;
		
		return (string) $lng->txt($a_name);
	}
	
	
	/** 
	 * Get a localized text from the plugin
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getPluginTxt','lang_var')
	 *
	 * @param 	string	lang var
	 * @return	string	text value
	 */
	static function getPluginTxt($a_name)
	{
		return (string) self::$plugin_object->txt($a_name);
	}
	
	
	/**
	 * Check if a skin template is already used
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getTemplateUsage','template_id')	  
	 * 
	 * @param 	string 	template_id, e.g. Services/MainMenu/tpl.main_menu.html
	 * @return	boolean
	 */
	static function getTemplateUsage($a_tpl_id)
	{
		return self::$gui_object->isTemplateUsed($a_tpl_id);
	}

	
	/**
	 * Check if any template of a component is already used
	 * or if the component is in the caller history
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getComponentUsage','component_id')	  
	 * 
	 * @param 	string 	component_id, e.g. Services/MainMenu
	 * @return	boolean
	 */
	static function getComponentUsage($a_comp_id)
	{
		return self::$gui_object->isComponentUsed($a_comp_id);
	}
	
	
	/**
	 * Get the current Url
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getCurrentUrl')	  
	 * 
	 * @param	boolean		convert url to lowercase before
	 * @return	string	URL
	 */
	static function getCurrentUrl($a_lowercase = false)
	{
		if ($a_lowercase)
		{
			return strtolower($_SERVER['REQUEST_URI']);
		}
		else
		{
			return (string) $_SERVER['REQUEST_URI'];
		}
	}

	
	/**
	 * Get a parameter from a url
	 *
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getUrlParameter','url', 'param_name')	  
	 *
	 * @param 	string		a url or empty (then the current url will be used)
	 * @param 	string		parameter name
	 * @param	boolean		convert url to lowercase before
	 * @return	string		parameter value
	 */
	static function getUrlParameter($a_url, $a_param, $a_lowercase = false)
	{
		if ($a_url == "")
		{
			$a_url = $_SERVER['REQUEST_URI'];
		}
		
		if ($a_lowercase)
		{
			$a_url = strtolower($a_url);
		}
		
		if (!is_int($pos = strpos($a_url, '?')))
		{
			return '';
		}
		
		parse_str(html_entity_decode(substr($a_url, $pos + 1)), $params);
		return (string) $params[$a_param];
	}
	

	/**
	 * Get the file name of a url
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::getUrlFile','url'')	  
	 *
	 * @param 	string		a url or empty (then the current url will be used)
	 * @return	string		file name
	 */
	static function getUrlFile($a_url)
	{
		if (is_int($pos = strpos($a_url, '?')))
		{
			return basename(substr($a_url, 0, $pos));
		}
		else
		{
			return basename($a_url);
		}
		
	}
	
	
	/**
	 * Add one or more parameters to a url
	 *
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::addUrlParameter','url', 'param_name', 'param_value', 'param_name', 'param_value', ...)	  
	 *
	 * @param 	string		a url
	 * @param 	string		parameter name
	 * @param	string		parameter value
	 * @return	string		url with added parameter
	 */
	static function addUrlParameter($a_url)
	{
		for ($i = 1; $i < func_num_args(); $i += 2)
		{
			if (is_int(strpos($a_url, '?')))
			{
				$a_url .= '&' . func_get_arg($i) . "=" . urlencode(func_get_arg($i+1)); 
			}
			else
			{
				$a_url .= '?' . func_get_arg($i) . "=" . urlencode(func_get_arg($i+1)); 
			}
		}
		
		return $a_url;
	}
	
	
	/**
	 * Checks if a url is supported
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::isUrlSupported','url', 'mode')	  
	 *
	 * @param 	string 		url
	 * @param 	string 		mode (optional)
	 * @return	boolean		
	 */
	static function isUrlSupported($a_url, $a_mode = '')
	{
		return self::$gui_object->isUrlSupported($a_url, $a_mode);	
	}
	
	/**
	 * Checks if a request is async
	 * 
	 * This function can be called from the XSL stylesheet using
	 * php:function('ilSkinTransformer::isAsync')	  
	 * 
	 * @return boolean
	 */
	static function isAsync()
	{
		global $ilCtrl;
		return $ilCtrl->isAsynch();
	}
}

?>
