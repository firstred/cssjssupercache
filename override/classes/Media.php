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

class Media extends MediaCore
{
    /**
     * Combine Compress and Cache (ccc) JS calls
     *
     * @param array $jsFiles
     * @return array processed js_files
     * @throws PrestaShopException
     */
    public static function cccJs($jsFiles)
    {
        if (!class_exists('CssJsSuperCache')) {
            require_once _PS_MODULE_DIR_.'cssjssupercache/cssjssupercache.php';
        }

        // If simulator is enabled, limit to selected IP addressses only
        // Also reject if module is not enabled for current shop
        if (Configuration::get(CssJsSuperCache::DEV_MODE) &&
            !in_array(Tools::getRemoteAddr(), explode(',', Configuration::get(CssJsSuperCache::DEV_MODE_IPS))) &&
            !Module::isEnabled('cssjssupercache')) {
            return parent::cccJS($jsFiles);
        }

        $fileCache = _PS_THEME_DIR_.'cache/jssupercache.php';
        $fileCacheIndex = _PS_THEME_DIR_.'cache/jssupercacheindex.php';
        /** @var CssJsSuperCache $module */
        $module = Module::getInstanceByName('cssjssupercache');

        if (file_exists($fileCache)) {
            $jsProfile = require($fileCache);
        } else {
            $jsProfile = array();
        }
        // First time
        if (!is_array($jsProfile) || empty($jsProfile)) {
            $jsProfile = $module->createFileCache($jsFiles);
            file_put_contents($fileCache, '<?php return Tools::jsonDecode(\''.Tools::jsonEncode($jsProfile).'\', true);');
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($fileCache);
            }
            $newCache = parent::cccJS($module->getJSFiles($jsProfile, true));
            file_put_contents($fileCacheIndex, '<?php return Tools::jsonDecode(\''.Tools::jsonEncode($newCache).'\', true);');
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($fileCacheIndex);
            }

            return $newCache;
        }

        // Detect if there is a difference
        $diffRemoved = array_diff($module->getJSFiles($jsProfile, true), $jsFiles);
        $diff = array_diff($jsFiles, $module->getJSFiles($jsProfile));
        if (count($diff) > 0 || count($diffRemoved) > 0) {
            $currentJsProfile = $module->createFileCache($jsFiles);
            $diffFiles = array();
            foreach ($currentJsProfile as $file) {
                if (!empty($file) && !empty($file['file']) && in_array($file['file'], $diff)) {
                    $diffFiles[] = $file;
                }
            }
            foreach ($diffFiles as $key => $value) {
                $jsProfile = $module->addJsFile($jsProfile, $value['file'], $module->findOptimalPosition($jsProfile, $value['before']));
            }
            file_put_contents($fileCache, '<?php return Tools::jsonDecode(\''.Tools::jsonEncode($jsProfile).'\', true);');
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($fileCache);
            }
            $newCache = parent::cccJS($module->getJSFiles($jsProfile, true));
            file_put_contents($fileCacheIndex, '<?php return Tools::jsonDecode(\''.Tools::jsonEncode($newCache).'\', true);');
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($fileCacheIndex);
            }

            return $newCache;
        }

        return require($fileCacheIndex);
    }
}