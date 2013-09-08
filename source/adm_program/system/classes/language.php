<?php
/*****************************************************************************/
/** @class Language
 *  @brief Reads language specific texts that are identified with text ids out of language xml files
 *
 *  The class will read a language specific text that is identified with their 
 *  text id out of an language xml file. The access will be manages with the
 *  SimpleXMLElement which search through xml files. An object of this class
 *  can't be stored in a PHP session because it creates PHP core objects which
 *  couldn't be stored in sessions. Therefore an object of @b LanguageData 
 *  should be assigned to this class that stored all necessary data and can be
 *  stored in a session.
 *  @par Examples
 *  @code // show how to use this class with the language data class and sessions
 *  script_a.php
 *  // create a language data object and assign it to the language object
 *  $language = new Language();
 *  $languageData = new LanguageData('de');
 *  $language->addLanguageData($languageData);
 *  $session->addObject('languageData', $languageData);
 *  
 *  script_b.php
 *  // read language data from session and add it to language object
 *  $language = new Language();
 *  $language->addLanguageData($session->getObject('languageData'));
 *
 *  // read and display a language specific text with placeholders for individual content
 *  echo $gL10n->get('MAI_EMAIL_SEND_TO_ROLE_ACTIVE', 'John Doe', 'Demo-Organization', 'Webmaster');@endcode
 */
/*****************************************************************************
 *
 *  Copyright    : (c) 2004 - 2013 The Admidio Team
 *  Homepage     : http://www.admidio.org
 *  License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 *****************************************************************************/

class Language
{
	private $languageData;					///< An object of the class @b LanguageData that stores all necessary language data in a session
	private $languages = array();			///< An Array with all available languages and their ISO codes
	private $xmlLanguageObjects = array();	///< An array with all SimpleXML object of the language from all paths that are set in @b $languageData.
	private $xmlReferenceLanguageObjects = array(); ///< An array with all SimpleXML object of the reference language from all paths that are set in @b $languageData.
	
	/** Adds a language data object to this class. The object contains all necessary
	 *  language data that is stored in the PHP session.
	 *  @param $languageDataObject An object of the class @b LanguageData.
	 */
	public function addLanguageData(&$languageDataObject)
	{
		if(is_object($languageDataObject))
		{
			$this->languageData =& $languageDataObject;
		}
	}

	/** Adds a new path of language files to the array with all language paths where Admidio 
	 *  should search for language files.
	 *  @param $path Server path where Admidio should search for language files.
	 */
	public function addLanguagePath($path)
	{
		$this->languageData->addLanguagePath($path);
	}
	
	/** Reads a text string out of a language xml file that is identified 
	 *  with a unique text id e.g. SYS_COMMON. If the text contains placeholders
	 *  than you must set more parameters to replace them.
	 *  @param $textId Unique text id of the text that should be read e.g. SYS_COMMON
	 *  @param $param1,$param2... The function accepts an undefined number of values which will be used to replace
     *                            the placeholder in the text.
     *                            $param1 will replace @b %%VAR1% or @b %%VAR1_BOLD%,
     *                            $param2 will replace @b %%VAR2% or @b %%VAR2_BOLD% etc.
	 *  @return Returns the text string with replaced placeholders of the text id.
     *  @par Examples
     *  @code // display a text without placeholders
     *  echo $gL10n->get('SYS_NUMBER');
     *
     *  // display a text with placeholders for individual content
     *  echo $gL10n->get('MAI_EMAIL_SEND_TO_ROLE_ACTIVE', 'John Doe', 'Demo-Organization', 'Webmaster');
     *  @endcode
	 */ 
    public function get($textId)
    {
		if(!is_object($this->languageData))
		    return 'Error: '.$this->languageData.' is not an object!';

        $text   = '';
		
		// first read text from cache if it exists there
        if(isset($this->languageData->textCache[$textId]))
        {
            $text = $this->languageData->textCache[$textId];
        }
        else
        {
			// search for text id in every SimpleXMLElement (language file) of the object array
			foreach($this->languageData->getLanguagePaths() as $languagePath)
			{
				if(strlen($text) == 0)
				{
					$text = $this->searchLanguageText($this->xmlLanguageObjects, $languagePath, $this->languageData->getLanguage(), $textId);
				}
			}
			
			// if text id wasn't found than search for it in reference language
			if(strlen($text) == 0)
			{
				// search for text id in every SimpleXMLElement (language file) of the object array
				foreach($this->languageData->getLanguagePaths() as $languagePath)
				{
					if(strlen($text) == 0)
					{
						$text = $this->searchLanguageText($this->xmlReferenceLanguageObjects, $languagePath, $this->languageData->getLanguage(true), $textId);
					}
				}
			}
		}

		if(strlen($text) > 0)
		{
			// replace placeholder with value of parameters
            $paramCount = func_num_args();
            $paramArray = func_get_args();
            
            for($paramNumber = 1; $paramNumber < $paramCount; $paramNumber++)
            {
				$text = str_replace('%VAR'.$paramNumber.'%', $paramArray[$paramNumber], $text);
				$text = str_replace('%VAR'.$paramNumber.'_BOLD%', '<strong>'.$paramArray[$paramNumber].'</strong>', $text);
            }
			
			// replace square brackets with html tags
			$text = strtr($text, '[]', '<>');
		}
					
		// no text found then write #undefined text#
		if(strlen($text) == 0)
		{
			$text = '#'.$textId.'#';
		}

		return $text;
    }

	/** Returns an array with all countries and their ISO codes
	 *  @return Array with all countries and their ISO codes e.g.: array('DEU' => 'Germany' ...)
	 */
	public function getCountries()
	{
		$countries = $this->languageData->getCountriesArray();
	
		if(count($countries) == 0)
		{
			// set path to language file of countries
			if(file_exists(SERVER_PATH.'/adm_program/languages/countries_'.$this->languageData->getLanguage().'.xml'))
			{
				$file = SERVER_PATH.'/adm_program/languages/countries_'.$this->languageData->getLanguage().'.xml';
			}
			elseif(file_exists(SERVER_PATH.'/adm_program/languages/countries_'.$this->languageData->getLanguage(true).'.xml'))
			{
				$file = SERVER_PATH.'/adm_program/languages/countries_'.$this->languageData->getLanguage(true).'.xml';
			}
			else
			{
				return array();
			}

			$data = implode('', file($file));
			$p = xml_parser_create();
			xml_parse_into_struct($p, $data, $vals, $index);
			xml_parser_free($p);

			for($i = 0; $i < count($index['ISOCODE']); $i++)
			{
				$countries[$vals[$index['ISOCODE'][$i]]['value']] = $vals[$index['NAME'][$i]]['value'];
			}
			$this->languageData->setCountriesArray($countries);
		}
		return $this->languageData->getCountriesArray();
	}
	
	/** Returns the name of the country in the language of this object. The country will be
	 *  identified by the ISO code e.g. 'DEU' or 'GBR' ...
	 *  @param $isoCode The three digits ISO code of the country where the name should be returned.
	 *  @return Return the name of the country in the language of this object.
	 */
	public function getCountryByCode($isoCode)
	{
		$countries = $this->languageData->getCountriesArray();
		
		if(count($countries) == 0)
		{
			$countries = $this->getCountries();
		}
		return $countries[$isoCode];
	}
	
	/** Returns the three digits ISO code of the country. The country will be identified
     *  by the name in the language of this object
	 *  @param $country The name of the country in the language of this object.
	 *  @return Return the three digits ISO code of the country.
	 */
	public function getCountryByName($country)
	{
		$countries = $this->languageData->getCountriesArray();
		
		if(count($countries) == 0)
		{
			$countries = $this->getCountries();
		}
		return array_search($country, $countries);
	}
	
	/** Returns the ISO code of the language of this object. 
	 *  @param $referenceLanguage If set to @b true than the ISO code of the reference language will returned.
	 *  @return Returns the ISO code of the language of this object or the reference language.
	 */
    public function getLanguage($referenceLanguage = false)
    {
		return $this->languageData->getLanguage($referenceLanguage);
    }


	/** Creates an array with all languages that are possible in Admidio.
	 *  The array will have the following syntax e.g.: array('DE' => 'deutsch' ...)
	 *  @return Return an array with all available languages.
	 */
	public function getAvaiableLanguages()
	{
		if(count($this->languages) == 0)
		{
			$data = implode('', file(SERVER_PATH.'/adm_program/languages/languages.xml'));
			$p = xml_parser_create();
			xml_parse_into_struct($p, $data, $vals, $index);
			xml_parser_free($p);

			for($i = 0; $i < count($index['ISOCODE']); $i++)
			{
				$this->languages[$vals[$index['ISOCODE'][$i]]['value']] = $vals[$index['NAME'][$i]]['value'];
			}
		}
		return $this->languages;
	}
	
	/** Search for text id in a language xml file and return the text. If no text was found than
	 *  nothing is returned.
	 *  @param $objectArray  The reference to an array where every SimpleXMLElement of each language path is stored
	 *  @param $languagePath The path in which the different language xml files are.
	 *  @param $language     The ISO code of the language in which the text will be searched
	 *  @param $textId       The id of the text that will be searched in the file.
	 *  @return Return the text in the language or nothing if text id wasn't found.
	 */
	public function searchLanguageText(&$objectArray, $languagePath, $language, $textId)
	{
		// if not exists create a SimpleXMLElement of the language file in the language path
		// and add it to the array of language objects
		if(array_key_exists($languagePath, $objectArray) == false)
		{
			$languageFile = $languagePath.'/'.$language.'.xml';
			
			if(file_exists($languageFile))
			{
				$objectArray[$languagePath] = new SimpleXMLElement($languageFile, 0, true);
			}
		}

		if(is_object($objectArray[$languagePath]))
		{
            // text not in cache -> read from xml file
			$node   = $objectArray[$languagePath]->xpath("/language/version/text[@id='".$textId."']");
			if($node != false)
			{
                // set line break with html
				$text = str_replace('\n', '<br />', $node[0]);
                // replace highly comma, so there are no problems in the code later
				$text = str_replace('\'', '&rsquo;', $text);
				$this->languageData->textCache[$textId] = $text;
				return $text;
			}
		}
		return '';
	}

	/** Set a language to this object. If there was a language before than initialize the cache
	 *  @param $language ISO code of the language that should be set to this object.
	 */
    public function setLanguage($language)
    {
		if($language != $this->languageData->getLanguage())
		{
			// initialize data
			$this->xmlLanguageObjects = array();
			$this->xmlReferenceLanguageObjects = array();
			
			$this->languageData->setLanguage($language);
		}
    }
}
?>