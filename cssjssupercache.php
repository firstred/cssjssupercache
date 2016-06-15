<?php
/**
 * 2016 Michael Dekker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@michaeldekker.com so we can send you a copy immediately.
 *
 *  @author    Michael Dekker <prestashop@michaeldekker.com>
 *  @copyright 2016 Michael Dekker
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CssJsSuperCache extends Module
{
    const DEV_MODE = 'CSSJSCACHE_DEV_MODE';
    const DEV_MODE_IPS = 'CSSJSCACHE_DEV_MODE_IPS';
    const JAVASCRIPT_FILES = 'CSSJSCACHE_JAVASCRIPT_FILES';

    /**
     * jsSuperCache constructor.
     */
    public function __construct()
    {
        $this->name = 'cssjssupercache';
        $this->version = '0.6.0';
        $this->author = 'Michael Dekker';
        $this->displayName = $this->l('CSS JS Super Cache');
        $this->description = $this->l('Combine CSS/JS files and decrease page load time.');
        $this->tab = 'administration';
        $this->bootstrap = true;

        parent::__construct();
    }

    /**
     * Install this module
     *
     * @return bool Whether installation succeeded
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        Configuration::updateValue(self::DEV_MODE, true);
        Configuration::updateValue(self::DEV_MODE_IPS, Tools::getRemoteAddr());

        return true;
    }

    /**
     * Uninstall this module
     *
     * @return bool Whether removal succeeded
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        Configuration::deleteByName(self::DEV_MODE);
        Configuration::deleteByName(self::DEV_MODE_IPS);

        return true;
    }

    /**
     * Get HTML of configuration form of this module
     *
     * @return string Configuration page HTML
     */
    public function getContent()
    {
        $output = '';
        $output .= $this->isCacheWritable();
        $output .= $this->postProcess();
        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');
        $output .= $this->displayForm();

        return $output;
    }

    /**
     * Process the form
     *
     * @return string Error messages HTML
     */
    public function postProcess()
    {
        $fileCacheLocation = _PS_THEME_DIR_.'cache/jssupercache.php';
        $fileCacheIndexLocation = _PS_THEME_DIR_.'cache/jssupercacheindex.php';
        if (file_exists($fileCacheLocation)) {
            $fileCache = require($fileCacheLocation);
        } else {
            $fileCache = array();
        }

        $output = '';
        if (Tools::isSubmit('empty_js_cache')) {
            file_put_contents($fileCacheLocation, '<?php return Tools::jsonDecode(\'[]\', true);');
            file_put_contents($fileCacheIndexLocation, '<?php return Tools::jsonDecode(\'[]\', true);');
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($fileCacheLocation);
                opcache_invalidate($fileCacheIndexLocation);
            }

            $output .= $this->displayConfirmation($this->l('JavaScript cache has been emptied!'));
        } elseif (Tools::isSubmit('submit'.$this->name)) {
            Configuration::updateValue(self::DEV_MODE, (int) Tools::getValue(self::DEV_MODE));
            Configuration::updateValue(
                self::DEV_MODE_IPS,
                str_replace("\n", ';', str_replace("\r", '', Tools::getValue(self::DEV_MODE_IPS)))
            );
            if (!empty($fileCache)) {
                foreach ($fileCache as $index => $fileInfo) {
                    if (!Tools::getValue(self::JAVASCRIPT_FILES.'_ENABLED_'.$index)) {
                        $this->jsFileActive($index, false);
                    } else {
                        $this->jsFileActive($index);
                    }
                }
            }
        } else {
            return $output;
        }

        return $output;
    }

    /**
     * Display main form HTML
     *
     * @return string Configuration form HTML
     */
    public function displayForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'module_page' => $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name
        );

        $forms = array($this->getSimulatorForm());
        $javaScriptForm = $this->getJavaScriptForm();
        if (!empty($javaScriptForm)) {
            $forms[] = $javaScriptForm;
            $this->context->controller->addCSS($this->_path.'views/css/file_list.css');
            if (version_compare(_PS_VERSION_, '1.6.0.3', '>=') === true) {
                $this->context->controller->addjqueryPlugin('sortable');
            } elseif (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
                $this->context->controller->addJS(_PS_JS_DIR_.'jquery/plugins/jquery.sortable.js');
            } else {
                $this->context->controller->addJS($this->_path.'views/js/jquery.sortable.js');
            }
            $this->context->controller->addJS($this->_path.'/views/js/file_list.js');
        }

        return $helper->generateForm($forms);
    }

    /**
     * Get simulator form elements
     *
     * @return array Array with simulator form elements
     */
    public function getSimulatorForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Developer mode'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Developer mode'),
                        'name' => self::DEV_MODE,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'label' => $this->l('Allowed IP addresses'),
                        'hint' => $this->l('IP addresses for which the JS and CSS cache will be combined'),
                        'type' => 'maintenance_ip',
                        'name' => self::DEV_MODE_IPS,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Get JavaScript Form elements
     *
     * @return array Form elements
     */
    public function getJavaScriptForm()
    {
        if (!file_exists(_PS_THEME_DIR_.'cache/jssupercache.php')) {
            $files = array();
        } else {
            $files = require(_PS_THEME_DIR_.'cache/jssupercache.php');
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('JavaScript'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'javascript_table',
                        'label' => $this->l('JavaScript Files'),
                        'hint' => $this->l('You can drag and drop files to change the position'),
                        'list_title' => (int) count($files).' '.$this->l('JavaScript Files'),
                        'name' => self::JAVASCRIPT_FILES,
                        'values' => $files,
                        'badge' => (int) count($files),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            )
        );
    }

    /**
     * Get all form values
     *
     * @return array Array with all form values
     */
    public function getFormValues()
    {
        $values = array(
            self::DEV_MODE => Configuration::get(self::DEV_MODE),
            self::DEV_MODE_IPS => Configuration::get(self::DEV_MODE_IPS),
        );

        return $values;
    }

    /**
     * Checks if the necessary directories and/or files are writable
     *
     * @return string Error messages HTML
     */
    public function isCacheWritable()
    {
        $output = '';
        $themes = Theme::getAllThemes()->getAll();
        foreach ($themes as $theme) {
            $themeCache = _PS_ALL_THEMES_DIR_.$theme->directory.'/cache';
            $themeTotalCache = _PS_ALL_THEMES_DIR_.$theme->directory.'/cache/jssupercache.php';
            $themeTotalCacheIndex = _PS_ALL_THEMES_DIR_.$theme->directory.'/cache/jssupercacheindex.php';

            if (file_exists($themeTotalCache) && file_exists($themeTotalCacheIndex)) {
                if (!is_writable($themeTotalCache)) {
                    $output .= $this->displayError(sprintf($this->l('The file %s is not writable. CSS and JS Total Cache will not work for the theme %s'), $themeCache, $theme->directory));
                }
                if (!is_writable($themeTotalCacheIndex)) {
                    $output .= $this->displayError(sprintf($this->l('The file %s is not writable. CSS and JS Total Cache will not work for the theme %s'), $themeTotalCacheIndex, $theme->directory));
                }
            } elseif (!is_writable($themeCache)) {
                $output .= $this->displayError(sprintf($this->l('The directory %s is not writable. CSS and JS Total Cache will not work for the theme %s'), $themeCache, $theme->directory));
            }
        }

        return $output;
    }

    /**
     * @param array $jsFiles
     * @return array
     */
    public function createFileCache($jsFiles)
    {
        $jsProfile = array();
        $beforeJs = array();
        foreach ($jsFiles as $index => $jsFile) {
            $jsProfile[$index] = array(
                'file' => $jsFile,
                'before' => $beforeJs,
                'locked' => false,
                'enabled' => true,
            );
            array_push($beforeJs, $jsFile);
        }

        return $jsProfile;
    }

    /**
     * Get array with js files
     *
     * @param array $fileCache Contents of file cache
     * @param bool  $active    Active only
     * @return array Array with js files
     */
    public function getJSFiles($fileCache, $active = false)
    {
        $files = array();
        if (empty($fileCache)) {
            return $files;
        }

        for ($i = 0; $i < count($fileCache); $i++) {
            if (array_key_exists('file', $fileCache[$i])) {
                if ($active) {
                    if (!$fileCache[$i]['enabled']) {
                        continue;
                    }
                    $fileCache[$i]['enabled'] = true;
                }
                $files[] = $fileCache[$i]['file'];
            }
        }

        return $files;
    }

    /**
     * Add js file to file cache
     *
     * @param array  $fileCache Contents of file cache
     * @param string $file      File name
     * @param array  $beforeJs
     * @return array Array: new File cache
     * @internal param int $pos Position
     */
    public function addJSFile($fileCache, $file, $beforeJs)
    {
        if (!Validate::isGenericName($file)) {
            return $fileCache;
        }
        $pos = $this->findOptimalPosition($fileCache, $file);
        for ($i = count($fileCache); $i > $pos; $i--) {
            $fileCache = $this->arrayMove($fileCache, $i-1, $i);
            $before = $fileCache[$i]['before'];
            for ($j = count($before); $j > $pos; $j--) {
                $before = $this->arrayMove($before, $j - 1, $j);
            }
            $fileCache[$i]['before'] = $before;
        }

        $before = array();
        if (!is_array($beforeJs) || empty($beforeJs)) {
            $beforeJs = array();
        }
        foreach ($beforeJs as $js) {
            $fileIndex = (int) $this->getJSFileIndex($fileCache, $js);
            if ($fileIndex > 0) {
                $before[$fileIndex] = $js;
            }
        }
        $fileCache[$pos] = array(
            'file' => $file,
            'before' => $before,
            'locked' => false,
            'enabled' => true,
        );

        return $fileCache;
    }

    /**
     * Get file location of file in file cache
     *
     * @param array  $fileCache The file cache
     * @param string $file      File name
     * @return int The index
     */
    public function getJSFileIndex($fileCache, $file)
    {
        foreach ($fileCache as $index => $fileInfo) {
            if ($fileInfo['file'] == $file) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * Enable/disable JS file
     *
     * @param int  $pos     Position
     * @param bool $enabled File enabled
     * @return bool Whether the change was successful
     */
    public function jsFileActive($pos, $enabled = true)
    {
        if (file_exists(_PS_THEME_DIR_.'cache/jssupercache.php')) {
            $fileCache = require(_PS_THEME_DIR_.'cache/jssupercache.php');
        } else {
            return false;
        }

        $fileCache[$pos]['enabled'] = (bool)$enabled;

        file_put_contents(_PS_THEME_DIR_.'cache/jssupercache.php', '<?php return Tools::jsonDecode(\''.Tools::jsonEncode($fileCache).'\', true);');
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(_PS_THEME_DIR_.'cache/jssupercache.php');
        }

        return true;
    }

    /**
     * Find the optimal position for the resource
     *
     * @param array $fileCache The file cache
     * @param array $beforeJs  All the JavaScript that should be placed before this file
     * @param null  $afterJs
     * @return int The optimal position
     */
    public function findOptimalPosition($fileCache, $beforeJs, $afterJs = null)
    {
        $position = 1;
        $jsFiles = $this->getJSFiles($fileCache);
        if (!is_array($beforeJs) || empty($beforeJs)) {
            $beforeJs = array();
        }
        foreach ($beforeJs as $file) {
            $foundPosition = (int) array_search($file, $jsFiles);
            if ($foundPosition > $position) {
                $position = $foundPosition;
            }
        }

        return $position;
    }

    /**
     * Clean JavaScript cache of current theme
     *
     * @return bool Cleaning was successful
     */
    public function cleanJSCache()
    {
        // Check if cleaning is allowed
        $shops = Shop::getShops(false);
        $theme = $this->theme(Shop::getContextShopID());

        $allowed = true;
        foreach ($shops as $shop) {
            if ($theme == $this->theme($shop['id_shop']) && !self::isEnabledForShop($shop['id_shop'])) {
                $allowed = false;
                break;
            }
        }

        return true;
    }


    /**
     * Is this module enabled for the shop?
     *
     * @param int $idShop Shop ID
     * @return bool Whether this module is enabled for the shop
     */
    public static function isEnabledForShop($idShop)
    {
        $idModule = Module::getModuleIdByName('jssupercache');
        $sql = new DbQuery();
        $sql->select('`id_module`');
        $sql->from('module_shop');
        $sql->where('`id_shop` = '.(int) $idShop);
        $sql->where('`id_module` = '.(int) $idModule);

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Move elem within array
     *
     * @param array $a The array
     * @param int $oldpos Old position
     * @param int $newpos New position
     * @return array
     */
    protected function arrayMove($a, $oldpos, $newpos)
    {
        if ($oldpos == $newpos) {
            return $a;
        }

        if (array_key_exists($oldpos, $a)) {
            $a[$newpos] = $a[$oldpos];
            unset($a[$oldpos]);
        }

        return $a;
    }

    /**
     * Find theme name of shop
     *
     * @param int $idShop Shop ID
     * @return string Theme name of shop
     */
    protected function theme($idShop)
    {
        $idShopGroup = (int) Shop::getGroupFromShop($idShop);
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $currentThemeSql = new DbQuery();
            $currentThemeSql->select('`theme_name`');
            $currentThemeSql->from('shop', 's');
            $currentThemeSql->where('`id_shop` = '.(int) $idShop.' AND `id_shop_group` = '.(int) $idShopGroup);

            return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($currentThemeSql);
        } else {
            $currentThemeSql = new DbQuery();
            $currentThemeSql->select('`t`.`name`');
            $currentThemeSql->from('shop', 's');
            $currentThemeSql->innerJoin('theme', 't', '`s`.`id_theme` = `t`.`id_theme`');
            $currentThemeSql->where('`id_shop` = '.(int) $idShop.' AND `id_shop_group` = '.(int) $idShopGroup);

            return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($currentThemeSql);
        }
    }
}
