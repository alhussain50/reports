<?php

namespace Harriswebworks\Reports\Helper;

//use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\Context;
use Harriswebworks\Reports\Helper\DetectDevice;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    const MOBILE = 'is_mobile';
    const TABLET = 'is_tablet';
    const DESKTOP = 'is_desktop';

    /**
     * @var bool
     */
    private $detected = false;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var MobileDetect
     */
    private $mobileDetect;
    protected $_storeManager;
    protected $_imageFactory;
    protected $_filesystem;
    protected $mediaDirectory;
    protected $filterProvider;
    protected $coreDate;
    protected $priceHelper;
    protected $categoryFactory;
    protected $customerSession;
    //protected $scopeConfig;
    const XML_PATH_CONFIG = 'hww_theme/';

    public function __construct(
    Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Filesystem $filesystem, \Magento\Framework\Image\AdapterFactory $imageFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, 
//            DetectDevice $mobileDetect, 
            \Magento\Cms\Model\Template\FilterProvider $filterProvider,
    \Magento\Framework\Stdlib\DateTime\DateTime $coreDate, \Magento\Framework\Pricing\Helper\Data $priceHelper,
    \Magento\Catalog\Model\CategoryFactory $categoryFactory,
    \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_coreRegistry = $registry;
        $this->_filesystem = $filesystem;
        $this->_imageFactory = $imageFactory;
        $this->filterProvider = $filterProvider;
        $this->coreDate = $coreDate;
        $this->priceHelper = $priceHelper;
//        $this->mobileDetect = $mobileDetect;
        $this->categoryFactory = $categoryFactory;
        //$this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
    }
    
    public function customerLoggedIn(){
    	return $this->customerSession->isLoggedIn();
    }

    public function getReportsConfig($config_path, $store = null) {
        $store = $this->_storeManager->getStore($store);
        $store = 0;
        $cnf = self::XML_PATH_CONFIG;
       // $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/hww.log');
      //  $logger = new \Zend\Log\Logger();
       // $logger->addWriter($writer);
        // $logger->info($cnf.$config_path);
        if ($config_path)
            return $this->scopeConfig->getValue($cnf . $config_path, ScopeInterface::SCOPE_STORE);
        //return true;
    }

    public function getConfig($config_path, $store = null) {
        $store = $this->_storeManager->getStore($store);
        return $this->scopeConfig->getValue($config_path, ScopeInterface::SCOPE_STORE, $store);
    }
    public function getCurrentCategory() {
        return $this->_coreRegistry->registry('current_category');
    }
    public function getCategoryData($id){
        return $category = $this->categoryFactory->create()->load($id);
    }
    public function mediaFileUrl($file='') {

        $fileURL = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $file;
        return $fileURL;
    }
    public function getFilterredBlock($data){
        if($data!=''){
            return $this->filterProvider->getBlockFilter()->setStoreId($this->_storeManager->getStore()->getId())->filter($data);
        }
        return '';
    }
     public function getCurrentDate(){
        return $this->coreDate->date("Y-m-d H:i:s");
     }
      public function getFormattedPrice($price){
        return $this->priceHelper->currency($price, true, false);
     }
    public function getResizedImage($image, $width = null, $height = null, $path = '', $attr = '', $urlonly=false) {
        $loadflag = $this->getReportsConfig('general/enabled');
        $image = trim($image, '/');
        $absolutePath = $this->getMediaDirectory()->getAbsolutePath($path . '/') . $image;
        $imageResized = $this->getMediaDirectory()->getAbsolutePath($path . '/resized/' . $width . '/') . $image;
        if (!file_exists($imageResized) && file_exists($absolutePath)) {
            //create image factory...
            $imageResize = $this->_imageFactory->create();
            $imageResize->open($absolutePath);
            $imageResize->constrainOnly(TRUE);
            $imageResize->keepTransparency(TRUE);
            $imageResize->keepFrame(FALSE);
            $imageResize->keepAspectRatio(TRUE);
            $imageResize->resize($width, $height);
            //destination folder
            $destination = $imageResized;
            //save image
            $imageResize->save($destination);
        }
        $resizedURL = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path . '/resized/' . $width . '/' . $image;
        if($urlonly){
            return $resizedURL;
        }
        if ($loadflag) {
            $src = 'data-src="' . $resizedURL . '"';
            $findme = 'class=';
            $pos = strpos($attr, $findme);
            if ($pos === false) {
                $attr = $attr . ' class="lozad"';
            } else {
                $attr = str_replace('class="', 'class="lozad ', $attr);
            }
        } else {
            $src = 'src="' . $resizedURL . '"';
        }
        $html = '<img ' . $src . '  width="' . $width . '" height="' . $height . '" ' . $attr . '/>';
        return $html;
    }
    public function getProductResizedImage($image, $width = null, $height = null,$basepath = '', $path = '') {
         $image = trim($image, '/');
        $absolutePath = $this->getMediaDirectory()->getAbsolutePath($basepath . '/') . $image;
        $targetdir = $this->getMediaDirectory()->getAbsolutePath($path );
        $imageResized =  $targetdir. '/' . $image;
        if(!is_dir($targetdir)){
       //echo $targetdir;
       mkdir($targetdir);
        }
         if (!file_exists($imageResized) && file_exists($absolutePath)) {
            //create image factory...
            $imageResize = $this->_imageFactory->create();
            $imageResize->open($absolutePath);
            $imageResize->constrainOnly(TRUE);
            $imageResize->keepTransparency(TRUE);
            $imageResize->keepFrame(true);
            $imageResize->backgroundColor([255, 255, 255]);
            $imageResize->keepAspectRatio(TRUE);
            $imageResize->resize($width, $height);
            //destination folder
            $destination = $imageResized;
            //save image
            $imageResize->save($destination);
        }

    }

    /**
     * @return bool
     */
    public function isDetected() {
        return $this->detected;
    }

    /**
     * If is mobile device
     * @return bool
     */
//    public function isMobile() {
//        $this->detected = self::MOBILE;
//        return $this->mobileDetect->isMobile();
//    }

    /**
     * If is a tablet
     * @return bool
     */
//    public function isTablet() {
//        $this->detected = self::TABLET;
//        return $this->mobileDetect->isTablet();
//    }

    /**
     * If is desktop device
     * @return bool
     */
    public function isDesktop() {
        if ($this->isMobile()) {
            return false;
        }
        $this->detected = self::DESKTOP;
        return true;
    }

    /**
     * The mobile detect instance to be able to use all the functionality
     * @return MobileDetect
     */
//    public function getMobileDetect() {
//        return $this->mobileDetect;
//    }
    public function getMediaDirectory()
    {
        if (!$this->mediaDirectory) {
            $this->mediaDirectory = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        }
        return $this->mediaDirectory;
    }



}
