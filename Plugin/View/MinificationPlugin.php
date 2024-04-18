<?php


namespace Harriswebworks\Reports\Plugin\View;

use \Magento\Framework\View\Asset\Minification;
use \Harriswebworks\Reports\Helper\Data;

/**
 * MinificationPlugin
 *
 * @package  Pmclain\AuthorizenetCim\Plugin
 * 
 */
 
class MinificationPlugin
{
    /**
     * Exclude static componentry files from being minified.
     *
     * Using the config node `minify_exclude` is not an option because it does
     * not get merged but overridden by subsequent modules.
     *
     * @see \Magento\Framework\View\Asset\Minification::XML_PATH_MINIFICATION_EXCLUDES
     *
     * @param Minification $subject
     * @param string[] $result
     * @param string $contentType
     * @return string[]
     */
	 /**
     * @var \Harriswebworks\Reports\Helper\Data
     */
	protected $_hwwReportsHelper;
	
	public function __construct(Data $dataHelper)
	{
		$this->_hwwReportsHelper = $dataHelper;	
	}
	
	public function aroundGetExcludes(Minification $subject, callable $proceed, $contentType)
    	{
		$result = $proceed($contentType);
		
		if ($contentType !== 'js') {
            		return $result;
        	}
		$filearray['js'][] = 'js/hww/intersection-observer.min.js';
		$filearray['js'][] = 'tinymce.js';
		$filearray['js'][] = 'CloudFlare_Plugin/lang/en.min.js';
		$filearray['js'][] = 'CloudFlare_Plugin/config.min.js';
		$jsfiles = $filearray['js'];
		foreach($jsfiles as $key=>$file){
			if ($contentType == 'js') {				
				$result[]= $this->_hwwReportsHelper->mediaFileUrl($file);
			}
		}
		$result[] = 'https://js.authorize.net/v1/Accept.js';	
        	return $result;
    	}
}
