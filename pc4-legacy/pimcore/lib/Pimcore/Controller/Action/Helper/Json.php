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

namespace Pimcore\Controller\Action\Helper;

class Json extends \Zend_Controller_Action_Helper_Json
{

    /**
     * @param mixed $data
     * @param bool $sendNow
     * @param bool $keepLayouts
     * @param bool $encodeData
     * @return string|void
     */
    public function direct($data, $sendNow = true, $keepLayouts = false, $encodeData = true)
    {
        if ($encodeData) {
            $data = \Pimcore\Tool\Serialize::removeReferenceLoops($data);
        }

        // hack for FCGI because ZF doesn't care of duplicate headers
        $this->getResponse()->clearHeader("Content-Type");

        $this->suppressExit = !$sendNow;

        $d = $this->sendJson($data, $keepLayouts, $encodeData);

        return $d;
    }
}
