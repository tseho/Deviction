<?php

/**
 * Deviction 1.0
 * Last edit : 30/09/2012
 * quentin@wippix.org
 * 
 *
 * Thanks to Brett Jankord for his work on device detection :
 * http://www.brettjankord.com/2012/01/16/categorizr-a-modern-device-detection-script/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * and GNU Lesser General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 * */
class Deviction {

    //If true, tablet detection will return 'desktop'
    public static $catergorize_tablets_as_desktops = FALSE;
    //If true, TV detection will return 'desktop'
    public static $catergorize_tvs_as_desktops = TRUE;
    //Allowed device types from this list : 'tablet', 'mobile', 'desktop', 'tv'
    private $deviceFormats = array('tablet', 'mobile', 'desktop', 'tv');
    //If device type can't be detected, this type will be used
    private $defaultDevice = 'desktop';
    //If true, $debugDevice will be used, whatever your device.
    private $debugMode = false;
    //The device to use when $debugMode is true
    private $debugDevice = 'desktop';
    private $device;
    
    const tablet = 'tablet';
    const mobile = 'mobile';
    const desktop = 'desktop';
    const tv = 'tv';

    private static $detectedDevice = NULL;

    /**
     * Deviction construct.
     * @param boolean $debugMode
     * @param string $debugDevice
     * @param string $defaultDevice
     * @param array $deviceFormats Devices allowed.
     * @throws InvalidArgumentException
     */
    public function __construct($debugMode = false, $debugDevice = 'desktop', $defaultDevice = 'desktop', $deviceFormats = array()) {
        
        $this->setDebugMode($debugMode);
        
        if($this->isDebugMode){
        	$this->destroyDeviceInSession();
        }
        
        if(is_array($deviceFormats) && sizeof($deviceFormats) > 0){
            $this->setAllDeviceFormats($deviceFormats);
        }
        
        //If the debug device isn't allowed
        if(!in_array($debugDevice, $this->getAllDeviceFormats())){
            throw new InvalidArgumentException("The device format '".$debugDevice."' isn't allowed.");
        }
        
        $this->setDebugDevice($debugDevice);
        
        //If the default device isn't allowed
        if(!in_array($defaultDevice, $this->getAllDeviceFormats())){
            throw new InvalidArgumentException("The device format '".$defaultDevice."' isn't allowed.");
        }
        
        $this->setDefaultDevice($defaultDevice);
        
        $format = null;

        //If device already exists in session
        if($this->getDeviceInSession() !== null){
                $format = $this->getDeviceInSession();
        //If we can't analyze the user agent
        }else if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            //And debug mode is activated
            if($this->debugMode === true){
                $format = $this->debugDevice;
            //Else we use the default device
            }else{
                $format = $this->getDefaultDevice();
            }
        }else{
            $format = $this->analyze();
        }
        
        if(!in_array($format, $this->getAllDeviceFormats())){
            throw new InvalidArgumentException("The device format '".$format."' isn't allowed.");
        }
                
        if($this->updateDeviceFormat($format) === false){
            throw new Exception('Fatal error : Deviction can\'t set the device format');
        }
    }
    
    /**
     * Update the list of authorized device formats
     * @param array $deviceFormats
     * @throws InvalidArgumentException
     */
    public function setAllDeviceFormats(array $deviceFormats){
        foreach($deviceFormats as $format){
            if(!is_string($format)){
                throw new InvalidArgumentException('A device format must be in string');
            }
        }
        $this->deviceFormats = $deviceFormats;
    }
    
    /**
     * Return the list of authorized device formats
     * @return array
     */
    public function getAllDeviceFormats(){
        return $this->deviceFormats;
    }
    
    /**
     * Return the default device format
     * @return string
     */
    public function getDefaultDevice(){
        return $this->defaultDevice;
    }
    
    /**
     * Set the default device format
     * @param type $defaultDevice
     * @throws InvalidArgumentException
     */
    public function setDefaultDevice($defaultDevice){
        if(!is_string($defaultDevice)){
            throw new InvalidArgumentException('The default device must be in string');
        }
        $this->defaultDevice = $defaultDevice;
    }

    /**
     * Return true if debuggind mode is enabled.
     * @return boolean
     */
    public function isDebugMode() {
        return $this->debugMode;
    }

    /**
     * Update the debugging mode
     * @param boolean $debugMode
     * @throws InvalidArgumentException
     */
    public function setDebugMode($debugMode) {
        if (!is_bool($debugMode)) {
            throw new InvalidArgumentException('DebugMode must be a boolean');
        }
        $this->debugMode = $debugMode;
    }

    /**
     * Return the debugging device format
     * @return string
     */
    public function getDebugDevice() {
        return $this->debugDevice;
    }

    /**
     * Update the debuggind device format
     * @param string $device
     * @throws InvalidArgumentException
     */
    public function setDebugDevice($device) {
        if (!in_array($device, $this->deviceFormats)) {
            throw new InvalidArgumentException('This device type isn\'t allowed.');
        }
        $this->debugDevice = $device;
    }
    
    /**
     * Start the session
     */
    protected function startSession(){
        // Check if session has already started, otherwise E_NOTICE is thrown
        if (session_id() == "") {
            session_start();
        }
    }

    /**
     * Set the current device to session
     * @param string $device
     */
    protected function setDeviceInSession($device) {
        $this->startSession();
        $_SESSION['deviction'] = $device;
    }

    /**
     * Return the device saved in session
     * @return null|string
     */
    protected function getDeviceInSession() {
        $this->startSession();
        if(isset($_SESSION['deviction'])){
            return $_SESSION['deviction'];
        }else{
            return null;
        }
    }
    
    /**
     * This function will destroy the device format stored in session.
     */
    protected function destroyDeviceInSession() {
    	$this->startSession();
    	unset($_SESSION['deviction']);
    }

    /**
     * Analyze the user agent and return the device format
     * @return string
     */
    protected static function analyze() {
        
        $ua = $_SERVER['HTTP_USER_AGENT'];
        
        // Check if user agent is a smart TV - http://goo.gl/FocDk
        if ((preg_match('/GoogleTV|SmartTV|Internet.TV|NetCast|NETTV|AppleTV|boxee|Kylo|Roku|DLNADOC|CE\-HTML/i', $ua))) {
            $format = "tv";
        }
        // Check if user agent is a TV Based Gaming Console
        else if ((preg_match('/Xbox|PLAYSTATION.3|Wii/i', $ua))) {
            $format = "tv";
        }
        // Check if user agent is a Tablet
        else if ((preg_match('/iP(a|ro)d/i', $ua)) || (preg_match('/tablet/i', $ua)) && (!preg_match('/RX-34/i', $ua)) || (preg_match('/FOLIO/i', $ua))) {
            $format = "tablet";
        }
        // Check if user agent is an Android Tablet
        else if ((preg_match('/Linux/i', $ua)) && (preg_match('/Android/i', $ua)) && (!preg_match('/Fennec|mobi|HTC.Magic|HTCX06HT|Nexus.One|SC-02B|fone.945/i', $ua))) {
            $format = "tablet";
        }
        // Check if user agent is a Kindle or Kindle Fire
        else if ((preg_match('/Kindle/i', $ua)) || (preg_match('/Mac.OS/i', $ua)) && (preg_match('/Silk/i', $ua))) {
            $format = "tablet";
        }
        // Check if user agent is a pre Android 3.0 Tablet
        else if ((preg_match('/GT-P10|SC-01C|SHW-M180S|SGH-T849|SCH-I800|SHW-M180L|SPH-P100|SGH-I987|zt180|HTC(.Flyer|\_Flyer)|Sprint.ATP51|ViewPad7|pandigital(sprnova|nova)|Ideos.S7|Dell.Streak.7|Advent.Vega|A101IT|A70BHT|MID7015|Next2|nook/i', $ua)) || (preg_match('/MB511/i', $ua)) && (preg_match('/RUTEM/i', $ua))) {
            $format = "tablet";
        }
        // Check if user agent is unique Mobile User Agent
        else if ((preg_match('/BOLT|Fennec|Iris|Maemo|Minimo|Mobi|mowser|NetFront|Novarra|Prism|RX-34|Skyfire|Tear|XV6875|XV6975|Google.Wireless.Transcoder/i', $ua))) {
            $format = "mobile";
        }
        // Check if user agent is an odd Opera User Agent - http://goo.gl/nK90K
        else if ((preg_match('/Opera/i', $ua)) && (preg_match('/Windows.NT.5/i', $ua)) && (preg_match('/HTC|Xda|Mini|Vario|SAMSUNG\-GT\-i8000|SAMSUNG\-SGH\-i9/i', $ua))) {
            $format = "mobile";
        }
        // Check if user agent is Windows Desktop
        else if ((preg_match('/Windows.(NT|XP|ME|9)/', $ua)) && (!preg_match('/Phone/i', $ua)) || (preg_match('/Win(9|.9|NT)/i', $ua))) {
            $format = "desktop";
        }
        // Check if agent is Mac Desktop
        else if ((preg_match('/Macintosh|PowerPC/i', $ua)) && (!preg_match('/Silk/i', $ua))) {
            $format = "desktop";
        }
        // Check if user agent is a Linux Desktop
        else if ((preg_match('/Linux/i', $ua)) && (preg_match('/X11/i', $ua))) {
            $format = "desktop";
        }
        // Check if user agent is a Solaris, SunOS, BSD Desktop
        else if ((preg_match('/Solaris|SunOS|BSD/i', $ua))) {
            $format = "desktop";
        }
        // Check if user agent is a Desktop BOT/Crawler/Spider
        else if ((preg_match('/Bot|Crawler|Spider|Yahoo|ia_archiver|Covario-IDS|findlinks|DataparkSearch|larbin|Mediapartners-Google|NG-Search|Snappy|Teoma|Jeeves|TinEye/i', $ua)) && (!preg_match('/Mobile/i', $ua))) {
            $format = "desktop";
        }
        // Otherwise assume it is a Mobile Device
        else {
            $format = "mobile";
        }

        return $format;
    }

    /**
     * Return the current device type
     * @return string
     */
    public function getDeviceFormat() {
        return $this->device;
    }
    
    /**
     * Set the current device format
     * @param string $format
     */
    private function setDeviceFormat($format){
        $this->device = $format;
    }

    /**
     * Update the current device format. This function allow you to overwrite the
     * default detection. If $ignoreDebugging is false and debugging is activated,
     * this function CAN'T overwrite the debugging device.
     * @param type $format
     * @param boolean $ignoreDebugging
     * @return boolean
     */
    public function updateDeviceFormat($format, $ignoreDebugging = false) {
        
        if (($ignoreDebugging === false && $this->debugMode === false) || $ignoreDebugging === true) {
            if (in_array($format, $this->deviceFormats)) {
            	if( ! $this->isDebugMode) {
                	$this->setDeviceInSession($format);
                }
                $this->setDeviceFormat($format);
                return true;
            }else{
                return false;
            }
        }else if($ignoreDebugging === false && $this->debugMode === true){
            if (in_array($this->debugDevice, $this->deviceFormats)) {
                $this->setDeviceFormat($this->debugDevice);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * Returns true if desktop user agent is detected
     * @return boolean
     */
    public static function isDesktop() {
        if (static::getDetectedDeviceFormat() === "desktop") {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Returns true if tablet user agent is detected
     * @return boolean
     */
    public static function isTablet() {
        if (static::getDetectedDeviceFormat() === "tablet") {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Returns true if tablet user agent is detected
     * @return boolean
     */
    public static function isTV() {
        if (static::getDetectedDeviceFormat() === "tv") {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Returns true if mobile user agent is detected
     * @return boolean
     */
    public static function isMobile() {
        if (static::getDetectedDeviceFormat() === "mobile") {
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Returns the detected device format
     * @return string
     */
    public static function getDetectedDeviceFormat(){
        if(static::$detectedDevice === null){
            static::$detectedDevice = static::analyze();
        }
        return static::$detectedDevice;
        
    }

}

?>