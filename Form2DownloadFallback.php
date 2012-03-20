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


class Form2DownloadFallback extends Frontend
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
		$this->import('Database');
		$this->import('Session');
		//$this->import('Environment');
		$this->import('Input');
		
	}
	
		
	/**
	 * Modify the Form Template string to add psydo hidden fields
	 * for sending individual data for each form included in an article
	 * called from getContentElement-HOOK Contao v 2.9 - 2.10
	 */
	public function modifyContentElements($objElement, $strBuffer)
	{
		if(TL_MODE == 'BE')	 return $strBuffer;
		
		if($objElement instanceof Form)
		{
			if($objElement->formID != 'form2download')
			{
				return $strBuffer;
			}
			
			
			
			//------ Workaround for Contao < 2.11.1 to handle include elements, like forms
			
			//-- Check if current page is a teasered page => only one form entry allowed
			$strArticleAlias = $this->Input->get('articles');			
			
			if(!strlen($strArticleAlias))
			{
				global $objPage;
				$objArticle = $this->Database->execute("SELECT * FROM tl_article WHERE pid=" . $objPage->id . " AND published=1 AND showTeaser!=1 ORDER BY sorting");
				//$arrArticles = $objArticle->fetchAllAssoc();
				
				while( $objArticle->next() )
				//foreach($arrArticles as $article)
				{
					//$objArticle->id = $article['id'];
					// check if the current form is a form2download Element
					$objCteForm = $this->Database->prepare("SELECT * FROM tl_content WHERE pid=? AND type=? AND form=?")
								->limit(1)
								->execute($objArticle->id, 'form', $objElement->id);
					if(!$objCteForm->numRows) continue;
					
					// check if there is a download element in the same article
					$objCteDownload = $this->Database->prepare("SELECT * FROM tl_content WHERE pid=? AND type=?")
				   				->limit(1)
				   				->execute($objArticle->id, 'download');
					if(!$objCteDownload->numRows) continue;
					
					
					// store processed values in global array and compare with last one
					if( !in_array($objArticle->id, $GLOBALS['ZFORM2DOWNLOAD']['articles']) )
					{
						 $GLOBALS['ZFORM2DOWNLOAD']['articles'][] = $objArticle->id;
						 break;
					}
									
				}
			}
			else // it is a teasered page
			{
				$objArticle = $this->Database->execute("SELECT * FROM tl_article WHERE alias=" . "'$strArticleAlias'" . " AND published=1");
				
				// check if the current form is a form2download Element
				$objCteForm = $this->Database->prepare("SELECT * FROM tl_content WHERE pid=? AND type=? AND form=?")
							->limit(1)
							->execute($objArticle->id, 'form', $objElement->id);
				if(!$objCteForm->numRows) return $strBuffer;
				
				// check if there is a download element in the same article
				$objCteDownload = $this->Database->prepare("SELECT * FROM tl_content WHERE pid=? AND type=?")
			   				->limit(1)
			   				->execute($objArticle->id, 'download');
				if(!$objCteDownload->numRows) return $strBuffer;
			}
			//------
				
			// add a unique id and add a new class
			$arrCssID = $objElement->cssID; 
			if(strlen($arrCssID[0]) > 0) $spacer = '_';
			$arrCssID[0] .= $space . 'form2download_form' . $objArticle->id . '_' . $objCteForm->id;
			if(strlen($arrCssID[1]) > 0) $spacer = ' ';
			$arrCssID[1] .= $spacer . 'form2download_form' . $spacer;
			
			$objElement->cssID = $arrCssID;
			
			//---
			// Add hidden fields
			$arrHiddenFields = array
			(
				'article_ID' => $objArticle->id, 
				'cte_id_form' => $objCteForm->id,
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
			//---
			
			// Modify depending on session
			$visible = true;
			//
			
			$session = $this->Session->getData();
			if( isset($session['zForm2Download']) && is_array($session['zForm2Download']) )
			{
				//$strEmailField = $this->getEmailFormField($objElement->id);
				
				$this->import('Form2Download');
				$strEmailField = $this->Form2Download->getEmailFormField($objElement->id);
				
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
				if( $session['zForm2Download'][$strClearEmail][$objArticle->id]['cte_id_form'] == $objCteForm->id )
				{
					$visible = false;
				}
			}
			
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
			
			
		}
		
		
		if($objElement instanceof ContentDownload)
		{
			$objCteForm = $this->Database->prepare("SELECT * FROM tl_content WHERE pid=? AND type=? AND invisible!=1")
							->limit(1)
							->execute($objElement->pid, 'form');
			if(!$objCteForm->numRows) return $strBuffer;
			
			$objForm =  $this->Database->execute("SELECT * FROM tl_form WHERE id=" . $objCteForm->form);
			if(!$objForm->numRows) return $strBuffer;
			
			
			// add a unique id and add a new class
			$arrCssID = $objElement->cssID; 
			if(strlen($arrCssID[0]) > 0) $spacer = '_';
			$arrCssID[0] .= $space . 'form2download_download' .$objElement->pid . '_' . $objElement->id;
			if(strlen($arrCssID[1]) > 0) $spacer = ' ';
			$arrCssID[1] .= $spacer . 'form2download_download' . $spacer;
			$objElement->cssID = $arrCssID;
			
			// Modify depending on session
			$visible = false;
			
			$session = $this->Session->getData();
			if(isset($session['zForm2Download']) && is_array($session['zForm2Download']) )
			{
				//$strEmailField = $this->getEmailFormField($objForm->id);
				
				$this->import('Form2Download');
				$strEmailField = $this->Form2Download->getEmailFormField($objForm->id);
				
				if(!strlen($strEmailField))
				{
					return '<span class="error" style="color: red;">' . $GLOBALS['TL_LANG']['MSC']['ZFORM2DOWNLOAD']['err_noEmailField'] . '</span>'; 
				}
				
				// get Email submitted from POST
				$strEmail = $this->Input->post($strEmailField);
				$strClearEmail = standardize($strEmail);
				
				// Visibility
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
	//private function getEmailFormField($intFormID)
	//{
	//	$this->import('Database');
	//	// get email form field name
	//	$objFormFields = $this->Database->prepare("SELECT * FROM tl_form_field WHERE pid=?")
	//					->execute($intFormID);
	//	if(!$objFormFields->numRows) return ;
	//	$arrFields = $objFormFields->fetchAllAssoc();
	//	
	//	$strEmailField = '';
	//	if(count($arrFields) != 1)
	//	{
	//		$strEmailField = $objFormFields->name;
	//	} 		
	//	else
	//	{
	//		foreach($arrFields as $field)
	//		{
	//			if($field['rgxp'] == 'email')
	//			{
	//				$strEmailField = $field['name'];
	//			}
	//		}
	//	}
	//	return $strEmailField;
	//}

}

?>