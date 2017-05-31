<?php

/**
 * @file fintoPlugin.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University Library
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class fintoPlugin
 * @ingroup plugins_generic_finto
 * @brief finto plugin class
 *
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class FintoPlugin extends GenericPlugin {

   function getName() {
        return 'fintoPlugin';
    }

    function getDisplayName() {
        return "Finto/YSO";
    }

    function getDescription() {
        return "Integrates YSO ontology from the Finnish Finto service to the keyword field in OJS3. Supports Finnish, English and Swedish.";
    }
	
    function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
				HookRegistry::register ('TemplateManager::include', array(&$this, 'handleTemplateDisplay'));				
        }
		return $success;    
	}
	
	/**
	 * @see TemplateManager::display()
	 */
	function handleTemplateDisplay($hookName, $args) {
		$templateMgr =& $args[0];
		$params =& $args[1];
		$request =& PKPApplication::getRequest();

		if (!isset($params['smarty_include_tpl_file'])) return false;

		switch ($params['smarty_include_tpl_file']) {
			case 'submission/submissionMetadataFormFields.tpl':
				$templateMgr->register_outputfilter(array($this, 'keywordsFilter'));
				break;
		}
		return false;
	}	
	
	/**
	 * Output filter adds Finto to supporting agencies fields by overriding the existing controlled vocabulary settings
	 * @param $output string
	 * @param $templateMgr TemplateManager
	 * @return $string
	 */
	function keywordsFilter($output, &$templateMgr) {
		
		$endPoint = '</script>';
		
		// en_US
		$startPoint = 'en_US-keywords\]\[\]",';
		$newscript = $this->ysoTagit('en');
		$output = preg_replace('#('.$startPoint.')(.*?)('.$endPoint.')#si', '$1'.$newscript.'$3', $output, 1);
		
		// fi_FI
		$startPoint = 'fi_FI-keywords\]\[\]",';
		$newscript = $this->ysoTagit('fi');
		$output = preg_replace('#('.$startPoint.')(.*?)('.$endPoint.')#si', '$1'.$newscript.'$3', $output, 1);		
		
		// sv_SE
		$startPoint = 'sv_SE-keywords\]\[\]",';
		$newscript = $this->ysoTagit('sv');
		$output = preg_replace('#('.$startPoint.')(.*?)('.$endPoint.')#si', '$1'.$newscript.'$3', $output, 1);		
		
		
		$templateMgr->unregister_outputfilter('keywordsFilter');
		
		return $output;
	}
	
	/**
	 * Get YSO tagit settings for selected locale
	 * @param $locale string
	 * @return $string
	 */
	function ysoTagit($locale){
		return "allowSpaces: true,
				tagSource: function(request, response){
						$.ajax({
							url: 'https://api.finto.fi/rest/v1/search?vocab=yso&lang=".$locale."',
							dataType: 'json',
							data: {
								query: request.term + '*'
							},
							success: 
										function( data ) {
										var output = data.results;
										response($.map(data.results, function(item) {
											return {
												label: item.prefLabel + ' [' + item.uri + ']',
												value: item.prefLabel + ' [' + item.uri + ']'
											}
										}));
							}	
							
						});
				}
			});

		});";
	}
	
	
	
	
   
}
?>