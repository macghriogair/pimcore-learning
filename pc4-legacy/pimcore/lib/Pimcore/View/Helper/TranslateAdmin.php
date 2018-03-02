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

use Pimcore\Translate\Admin as TranslateAdapter;

class TranslateAdmin extends \Zend_View_Helper_Translate
{

    /**
     * @var \Pimcore\Translate
     */
    protected $translator;

    /**
     * @param string $key
     * @return mixed|string
     * @throws \Zend_Exception
     * @throws \Zend_View_Exception
     */
    public function translateAdmin($key = "")
    {
        if ($key) {
            $locale = $_REQUEST["systemLocale"];

            if (!$locale) {
                if (\Zend_Registry::isRegistered("Zend_Locale")) {
                    $locale = \Zend_Registry::get("Zend_Locale");
                } else {
                    $locale = new \Zend_Locale("en");
                }
            }

            if ($locale) {
                if (!$this->getTranslator()) {
                    $translate = new TranslateAdapter($locale);
                    $this->setTranslator($translate);
                }
                $this->setLocale($locale);

                return call_user_func_array([$this, "translate"], func_get_args());
            }
        }

        return $key;
    }

    /**
     * @param \Zend_Translate|\Zend_Translate_Adapter $translator
     * @return null|\Zend_View_Helper_Translate
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return null|\Pimcore\Translate|\Zend_Translate_Adapter
     */
    public function getTranslator()
    {
        return $this->translator;
    }
}
