<?php
namespace Wigit;

/**
 * @category Config
 * @package  Wigit
 * @author   Till Klampaeckel <till@php.net>
 * @version
 * @license
 * @link     http://github.com/till/wigit
 */
class Config
{
    public $git            = 'git';
    public $base_url       = '/wigit';
    public $script_url;
    public $title          = 'WiGit';
    public $data_dir       = 'data';
    public $default_page   = 'Home';
    public $default_author = 'Anonymous <anon@wigit>';
    public $authors        = array();
    public $theme          = 'default';

    /**
     * Constructor.
     *
     * @return $this
     */
    public function __construct()
    {
        $this->script_url = $this->base_url . '/index.php?r=';
    }

    /**
     * Attempt to load a local config and overwrite defaults.
     *
     * @param string $configFile A complete path.
     *
     * @return boolean
     */
    public function checkLocalConfig($configFile)
    {
        if (!file_exists($configFile)) {
            return false;
        }
        $arr = include $configFile;
        if (!is_array($arr)) {
            return false;
        }
        foreach ($arr as $configKey => $configValue) {
            $this->$configKey = str_replace(
                '__BASE_URL__',
                $this->base_url,
                $configValue
            );
        }
        return true;
    }
}