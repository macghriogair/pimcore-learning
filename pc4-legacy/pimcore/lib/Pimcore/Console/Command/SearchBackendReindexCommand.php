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

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\Search;
use Pimcore\Logger;

class SearchBackendReindexCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('search-backend-reindex')
            ->setDescription("Re-indexes the backend search of pimcore");
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // clear all data
        $db = \Pimcore\Db::get();
        $db->query("TRUNCATE `search_backend_data`;");

        $elementsPerLoop = 100;
        $types = ["asset", "document", "object"];

        foreach ($types as $type) {
            $listClassName = "\\Pimcore\\Model\\" . ucfirst($type) . "\\Listing";
            $list = new $listClassName();
            if (method_exists($list, "setUnpublished")) {
                $list->setUnpublished(true);
            }

            $elementsTotal = $list->getTotalCount();

            for ($i=0; $i<(ceil($elementsTotal/$elementsPerLoop)); $i++) {
                $list->setLimit($elementsPerLoop);
                $list->setOffset($i*$elementsPerLoop);

                $this->output->writeln("Processing " .$type . ": " . ($list->getOffset()+$elementsPerLoop) . "/" . $elementsTotal);

                $elements = $list->load();
                foreach ($elements as $element) {
                    try {
                        $searchEntry = Search\Backend\Data::getForElement($element);
                        if ($searchEntry instanceof Search\Backend\Data and $searchEntry->getId() instanceof Search\Backend\Data\Id) {
                            $searchEntry->setDataFromElement($element);
                        } else {
                            $searchEntry = new Search\Backend\Data($element);
                        }

                        $searchEntry->save();
                    } catch (\Exception $e) {
                        Logger::err($e);
                    }
                }
                \Pimcore::collectGarbage();
            }
        }

        $db->query("OPTIMIZE TABLE search_backend_data;");
    }
}
