<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  	Tim Gatzky 2012 
 * @author     	Tim Gatzky <info@tim-gatzky.de>
 * @package    	zForm2Download
 * @client		Insignum Werbeagentur GbR <www.insignum.de> 
 * @license    	LGPL 
 * @filesource
 */


class Form2Download extends Frontend
{
	/**
	 * table
	 */
	protected $strTable = 'tl_zform2download';
	
	/**
	 * Initialize
	 */
	public function __construct()
	{
		$this->import('Session');
		$this->import('Input');
		$this->import('Database');
		$this->import('Environment');
	}
	
	
		
	/**
	 * Modify the Form Template string to add psydo hidden fields
	 * for sending individual data for each form included in an article
	 * called from getContentElement-HOOK 
	 */
	public function modifyContentElements(Database_Result $objRow, $strBuffer)
	{
		//$this->import('Session');
		//$this->Session->remove('zForm2Download');
		
		if($objRow->type == 'form')
		{
			$strClass = $this->findContentElement($objRow->type);
       		$objElement = new $strClass($objRow); // is type of 'Form'    
			
			if($objElement->formID != 'form2download' && standardize($objElement->formID) != 'form2download' )
			{
				return $strBuffer;
			}
					
			
			// check if there is a download element in the same article
			$objCteDownload = $this->Database->prepare("SELECT * FROM tl_content WHERE pid=? AND type=?")
			   			->limit(1)
			   			->execute($objRow->pid, 'download');
			
			// if there is no download element next to the form2download form, return
			if(!$objCteDownload->numRows) return $strBuffer;
			//$arrCteDownload = $objCteDownload->row();
			
			// add a unique id and add a new class
			$arrCssID = $objElement->cssID; 
			if(strlen($arrCssID[0]) > 0) $spacer = '_';
			$arrCssID[0] .= $space . 'form2download_form' . $objRow->pid . '_' . $objRow->pid;
			if(strlen($arrCssID[1]) > 0) $spacer = ' ';
			$arrCssID[1] .= $spacer . 'form2download_form' . $spacer;
			
			$objElement->cssID = $arrCssID;
			
			//--- ok
						
			// Add hidden fields
			$arrHiddenFields = array
			(
				'article_ID' => $objRow->pid, 
				'cte_id_form' => $objRow->id,
				'cte_id_download' => $objCteDownload->id,
				'cte_download_singleSRC' => $objCteDownload->singleSRC,
				
			);
			
			$strHiddenFields = '';
			foreach($arrHiddenFields as $k => $v)
			{
				$objHiddenField = new FormHidden(); 
				$objHiddenField->name = strtoupper($k);
				$objHiddenField->value = $v;
				$strHiddenFields .= $objHiddenField->parse();
			}
			
			
			// Modify depending on session
			$visible = true;
			
			$this->import('Session');
			$session = $this->Session->getData();
						
			if(isset($session['zForm2Download']) && is_array($session['zForm2Download']) )
			{
				$strEmailField = $this->getEmailFormField($objElement->id);
				
				if(!strlen($strEmailField))
				{
					return '<span class="error" style="color: red;">' . $GLOBALS['TL_LANG']['MSC']['ZFORM2DOWNLOAD']['err_noEmailField'] . '</span>'; 
				}
				
				// get Email submitted from POST
				$this->import('Input');
				$strEmail = $this->Input->post($strEmailField);
				$strClearEmail = standardize($strEmail);
				
				// Visibility 
				// if session is set for this element, visiblity of form = false
				if( $session['zForm2Download'][$strClearEmail][$objRow->pid]['cte_id_form'] == $objRow->id )
				{
					$visible = false;
				}
			}
			
			// generate modified cte, or return empty string when not visible
			if($visible)
			{
				// Generate Form
				$strForm = $objElement->generate();
				
				// find first <input>
				preg_match('/<input.*/',$strForm,$erg);
				$firstInput = $erg[0];
				
				// replace and insert new hidden field string
				$strFormModified = str_replace($firstInput, $firstInput . $strHiddenFields, $strForm);
				
				// send back modified cte string
				$strBuffer = $strFormModified; 
			}
			else
			{
				$strBuffer = '';
			}
			
			// show an <a> to remove Session Data
			if($GLOBALS['ZFORM2DOWNLOAD']['debugMode'])
			{
				 
			}
		}
		
		
		if($objRow->type == 'download')
		{
			$strClass = $this->findContentElement($objRow->type);
       		$objElement = new $strClass($objRow);  
       		
       		// 1. check if there is a form2download form inside this article as well
			$this->import('Database');
			$objCteForm = $this->Database->prepare("SELECT form FROM tl_content WHERE pid=? AND type=?")
			   			->limit(1)
			   			->execute($objElement->pid, 'form');
			if(!$objCteForm->numRows) return $strBuffer;
			
			// 2. check if the form is a form2download form
			$objForm = $this->Database->prepare("SELECT * FROM tl_form WHERE id=? AND formID=?")
							->limit(1)
							->execute($objCteForm->form,'form2download');
			if(!$objForm->numRows) return $strBuffer;
			
			//-- ok
			
			// add a unique id and add a new class
			$arrCssID = $objElement->cssID; 
			if(strlen($arrCssID[0]) > 0) $spacer = '_';
			$arrCssID[0] .= $space . 'form2download_download' . $objRow->pid . '_' . $objRow->pid;
			if(strlen($arrCssID[1]) > 0) $spacer = ' ';
			$arrCssID[1] .= $spacer . 'form2download_download' . $spacer;
			$objElement->cssID = $arrCssID;
			
			// Modify depending on session
			$visible = false;
			
			$this->import('Session');
			$session = $this->Session->getData();
			
			if(isset($session['zForm2Download']) && is_array($session['zForm2Download']) )
			{
				$strEmailField = $this->getEmailFormField($objForm->id);
				
				if(!strlen($strEmailField))
				{
					return '<span class="error" style="color: red;">' . $GLOBALS['TL_LANG']['MSC']['ZFORM2DOWNLOAD']['err_noEmailField'] . '</span>'; 
				}
				
				// get Email submitted from POST
				$this->import('Input');
				$strEmail = $this->Input->post($strEmailField);
				$strClearEmail = standardize($strEmail);
				
				// if session is set for this element, visiblity of download = true
				if( $session['zForm2Download'][$strClearEmail][$objElement->pid]['cte_id_download'] == $objElement->id )
				{
					$visible = true;
				}
			}
			
			// generate modified cte, or return empty string when not visible
			if($visible)
			{	
				$strBuffer = $objElement->generate(); 
			}
			else
			{
				$strBuffer = '';
			}
		}
		
		return $strBuffer;
	}
	
	/**
	 * Helper function to get the name of the email input form field 
	 * @param int : form id
	 * @return string
	 */
	public function getEmailFormField($intFormID)
	{
		$this->import('Database');
		// get email form field name
		$objFormFields = $this->Database->prepare("SELECT * FROM tl_form_field WHERE pid=?")
						->execute($intFormID);
		if(!$objFormFields->numRows) return ;
		$arrFields = $objFormFields->fetchAllAssoc();
		
		$strEmailField = '';
		if(count($arrFields) == 1)
		{
			$strEmailField = $objFormFields->name;
		} 		
		else
		{
			foreach($arrFields as $field)
			{
				if($field['rgxp'] == 'email')
				{
					$strEmailField = $field['name'];
				}
				else if ($field['name'] == 'email')
				{
					$strEmailField = $field['name'];
				}
				
			}
		}
		return $strEmailField;
	}

	
	/**
	 * Create new columns in tl_zform2download when the form contains fields not created by default
	 * Called from storeFormData-HOOK Contao >= 2.11 
	 */
	public function storeFormDataHook($arrData, $arrForm)
	{
		// Create fields in 'tl_zform2download' if not exists
		$this->import('Database');
		
		$arrDbFields =  $this->Database->getFieldNames($this->strTable);
		foreach($arrData as $f => $v)
		{
			if(!in_array($f, $arrDbFields))
			{
				$this->Database->execute("ALTER TABLE " . $this->strTable . " ADD " . $f . " varchar(255) NOT NULL default ''");
			}
		}
		
		$arrData = array();
		return $arrData;
	}
	

	/**
	 * 
	 * Called from processFormData-HOOK
	 */
	public function processFormDataHook($arrPost, $arrForm, $arrFiles)
	{
		
		// only work on forms with id containing "form2download"
		if ( $arrPost['FORM_SUBMIT'] == 'auto_form2download' || strpos($arrPost['FORM_SUBMIT'], 'form2download') )
		{
			$strEmail = $arrPost['email'];
			$strFile = $arrPost['CTE_DOWNLOAD_SINGLESRC'];
			
			// Store Data
			if(($arrForm['storeFormdata'] || $arrForm['storeValues']) && $arrForm['targetTable'] == $this->strTable )
			{
				$tstamp = time();
				$this->import('Database');
				
				//-- Delete entries made by contao
				$objContaoEntry = $this->Database->execute("SELECT id FROM " . $this->strTable .  " WHERE length(file)=0");
				if($objContaoEntry->numRows) 
				{
					$this->Database->execute("DELETE FROM " . $this->strTable .  " WHERE length(file)=0");
				}
				// reset auto_increment to latest id
				$objIds = $this->Database->execute("SELECT COUNT(*) AS counter FROM "  . $this->strTable . "");
				$this->Database->execute("ALTER TABLE " . $this->strTable . " AUTO_INCREMENT=" . $objIds->counter);
				//--
				
				// check if email and file already exists in same row
				$objFields = $this->Database->prepare("SELECT * FROM "  . $this->strTable . " WHERE email=? AND file=?")
								->limit(1)
								->execute($strEmail, $strFile);
				
								
				// if email and file not exist together -> create new row
				if(!$objFields->numRows) 
				{
					$this->Database->prepare("INSERT INTO " . $this->strTable . " (tstamp, email, file, download_count, confirmation) VALUES (?, ?, ?, ?, ?)")
								->execute(time(), $strEmail, $strFile, 1, $arrPost['confirmation']);
					
					// The current column id if the autoincrement value + 1, because of the new row just inserted
					$intCurrColumnID = $objIds->counter + 1;
				}
				else // increase download_count and update timestamp
				{
					$newCount = $objFields->download_count + 1;
					$this->Database->prepare("UPDATE " . $this->strTable .  " SET tstamp=?, download_count=?, confirmation=? WHERE id=?")
							->execute(time(), $newCount, $objFields->id, $arrPost['confirmation']);
					
					// The current column id is the one of the row that is updated
					$intCurrColumnID = $objFields->id;
				}
				
								
				
				// Handle data for not default fields
				$arrDbFieldsDefault = array('id','tstamp','email','field','download_count','confirmation');
				$objNonDefaultFields = $this->Database->prepare("SELECT * FROM tl_form_field WHERE pid=?")
								->execute($arrForm['id']);
				
				$arrNonDefaultFields = $objNonDefaultFields->fetchAllAssoc();
				
				/**
				 * Create new columns in tl_zform2download when the form contains fields not created by default
				 * Fallback for storeFormData-HOOK Contao >= 2.11 
				 */
				//if (version_compare(VERSION . '.' . BUILD, '2.11.1', '<'))
				//{
				//	foreach($arrNonDefaultFields as $f)
				//	{
				//		if(!in_array($f['name'], $arrDbFieldsDefault) )
				//		{
				//			$field = $f['name'];
				//			$this->Database->execute("ALTER TABLE " . $this->strTable . " ADD " . $field . " varchar(255) NOT NULL default ''");
				//		}
				//	}
				//}
				
				// Update routine
				foreach($arrNonDefaultFields as $f)
				{
					if(!in_array($f['name'], $arrDbFieldsDefault) )
					{
						$field = $f['name'];
						$value = $arrPost[$field];
						$this->Database->prepare("UPDATE " . $this->strTable .  " SET " . $field .  "=? WHERE id=?")
							->execute($value,$intCurrColumnID);
					}
				}
				//--
				
				
				
			}	

			// create new Session
			$this->import('Session');
			$session = $this->Session->getData();
			
			// clear Session
			//$this->Session->remove('zForm2Download');
						
			// collect all for session
			$strClearEmail = standardize($strEmail);
			$article = $arrPost['ARTICLE_ID'];
			$file = $arrPost['CTE_DOWNLOAD_SINGLESRC'];
			$arrData = array
			(
				'email' => $strEmail,
				'article' => $article,
				'cte_id_form' => $arrPost['CTE_ID_FORM'],
				'cte_id_download' => $arrPost['CTE_ID_DOWNLOAD'],
				'cte_download_singleSRC' => $file
			);
			
			$session['zForm2Download'][$strClearEmail][$article] = $arrData;
			$this->Session->setData($session);
			

			// if redirect directely to file
			$jumpToFile = $GLOBALS['ZFORM2DOWNLOAD']['jumpToFile'];
			if($jumpToFile)
			{
				$this->sendFileToBrowser($file);
			}
			
			// if jumpTo page is set
			//if($arrForm['jumpTo'])
			//{
			//	$objNextPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")
			//					->limit(1)
			//					->execute($arrForm['jumpTo']);
			//	if ($objNextPage->numRows)
			//	{
			//		$strParams = '';
			//		$this->redirect( $this->generateFrontendUrl($objNextPage->fetchAssoc(), $strParams, $strForceLang) );
			//		}
			//	}
			//	$this->reload();
			//}
						
			// set anchor to download element
			if($GLOBALS['ZFORM2DOWNLOAD']['setAnchorToFile'])
			{
				$strUrl = $this->Environment->request;
				$strUrl .= '#form2download_download' . $arrPost['ARTICLE_ID'] . '_' . $arrPost['CTE_ID_DOWNLOAD'];
				$this->redirect($strUrl);
			}
			//var_dump('---');
		}
		
		return $arrSubmitted;
 	}
	
 	
 	
 	
}

?>