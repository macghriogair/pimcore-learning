<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\View\Helper;

use Pimcore\Cache as CacheManager;

class Cache extends \Zend_View_Helper_Abstract
{

    /**
     * @var CacheController
     */
    public static $_caches;

    /**
     * @param $name
     * @param null $lifetime
     * @param bool $force
     * @return mixed
     */
    public function cache($name, $lifetime = null, $force = false)
    {
        if (self::$_caches[$name]) {
            return self::$_caches[$name];
        }

        $cache = new CacheController($name, $lifetime, $this->view->editmode, $force);
        self::$_caches[$name] = $cache;

        return self::$_caches[$name];
    }
}


class CacheController
{

    /**
     * @var
     */
    public $cache;

    /**
     * @var string
     */
    public $key;

    /**
     * @var bool
     */
    public $editmode;

    /**
     * @var bool
     */
    public $captureEnabled = false;

    /**
     * @var bool
     */
    public $force = false;

    /**
     * @param $name
     * @param $lifetime
     * @param bool $editmode
     * @param bool $force
     */
    public function __construct($name, $lifetime, $editmode = true, $force = false)
    {
        $this->key = "pimcore_viewcache_" . $name;
        $this->editmode = $editmode;
        $this->force = $force;
        
        if (!$lifetime) {
            $lifetime = null;
        }

        $this->lifetime = $lifetime;
    }

    /**
     * @return bool
     */
    public function start()
    {
        if (\Pimcore\Tool::isFrontentRequestByAdmin() && !$this->force) {
            return false;
        }
        
        if ($content = CacheManager::load($this->key)) {
            echo $content;

            return true;
        }
        
        $this->captureEnabled = true;
        ob_start();
        
        return false;
    }

    /**
     *
     */
    public function end()
    {
        if ($this->captureEnabled) {
            $this->captureEnabled = false;
            
            $tags = ["in_template"];
            if (!$this->lifetime) {
                $tags[] = "output";
            }
    
            $content = ob_get_clean();
            CacheManager::save($content, $this->key, $tags, $this->lifetime, 996, true);
            echo $content;
        }
    }

    /**
     *
     */
    public function stop()
    {
        $this->end();
    }
}
