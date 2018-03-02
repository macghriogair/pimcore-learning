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


use Pimcore\WorkflowManagement\Workflow;
use Pimcore\Model\Object;
use Pimcore\Model\Object\Concrete as ConcreteObject;
use Pimcore\Model\Document;
use Pimcore\Model\Asset;

class Admin_WorkflowController extends \Pimcore\Controller\Action\Admin\Element
{

    /**
     * @var Workflow\Manager $manager;
     */
    private $manager;

    /**
     * @var Workflow\Decorator $decorator;
     */
    private $decorator;

    /**
     * @var Document|Asset|ConcreteObject $element
     */
    private $element;

    /**
     * @var string $selectedAction
     */
    private $selectedAction;

    /**
     * @var string $newState
     */
    private $newState;

    /**
     * @var string $newStatus
     */
    private $newStatus;


    public function preDispatch()
    {
        parent::preDispatch();

        if ($this->getParam('ctype') === 'document') {
            $this->element = Document::getById((int) $this->getParam('cid', 0));
        } elseif ($this->getParam('ctype') === 'asset') {
            $this->element = Asset::getById((int) $this->getParam('cid', 0));
        } elseif ($this->getParam('ctype') === 'object') {
            $this->element = ConcreteObject::getById((int) $this->getParam('cid', 0));
        }

        if (!$this->element) {
            throw new \Exception('Cannot load element' . $this->getParam('cid') . ' of type \'' . $this->getParam('ctype') . '\'');
        }

        //get the latest available version of the element -
        $this->element = $this->getLatestVersion($this->element);
        $this->element->setUserModification($this->getUser()->getId());
    }


    /**
     * Returns a JSON of the available workflow actions to the admin panel
     */
    public function getWorkflowFormAction()
    {
        $params = $this->getParam('workflow', []);
        $manager = $this->getWorkflowManager();
        $workflow = $manager->getWorkflow();

        //this is the default returned workflow data
        $wfConfig = [
            'message'               => '',
            'available_actions'     => [],
            'available_states'      => [],
            'available_statuses'    => [],
            'notes_required'        => false,
            'additional_fields'     => []
        ];

        try {

            //get user selections
            $this->selectedAction = empty($params['action'])    ? null : $params['action'];
            $this->newState       = empty($params['newState'])  ? null : $params['newState'];
            $this->newStatus      = empty($params['newStatus']) ? null : $params['newStatus'];

            //always return the available actions
            $wfConfig['available_actions'] = $this->getDecorator()->getAvailableActionsForForm(
                $manager->getAvailableActions()
            );

            //if only one action select it by default
            if (count($wfConfig['available_actions']) === 1) {
                $this->selectedAction = $wfConfig['available_actions'][0]['value'];
            } elseif ($this->selectedAction && !$workflow->isValidAction($this->selectedAction)) {
                $this->selectedAction = null;
            }


            //if user has selected an action & it's valid
            if ($this->selectedAction) {
                
                //set the available states for this action
                $wfConfig['available_states'] = $this->getDecorator()->getAvailableStatesForForm(
                    $manager->getAvailableStates($this->selectedAction)
                );

                //validate the new state
                if (count($wfConfig['available_states']) === 1) {
                    $this->newState = $wfConfig['available_states'][0]['value'];
                } elseif ($this->newState && !$workflow->isValidState($this->newState)) {
                    $this->newState = null;
                }

                //load the available statuses, notes and additional fields
                if ($this->newState) {
                    $wfConfig['available_statuses'] = $this->getDecorator()->getAvailableStatusesForForm(
                        $manager->getAvailableStatuses($this->selectedAction, $this->newState)
                    );

                    // fetch additional fields, using the current status of the element
                    // to load additional fields that may be required
                    $wfConfig['notes_required'] = $manager->getNotesRequiredForAction($this->selectedAction);
                    $wfConfig['additional_fields'] = $manager->getAdditionalFieldsForAction($this->selectedAction);
                }
            }
        } catch (\Exception $e) {
            $wfConfig['message'] = $e->getMessage();
        }

        $this->_helper->json($wfConfig);
    }


    public function submitWorkflowTransitionAction()
    {
        $manager = $this->getWorkflowManager();
        $params = $this->getParam('workflow', []);

        if ($manager->validateAction($params['action'], $params['newState'], $params['newStatus'])) {


            //perform the action on the element
            try {
                $manager->performAction($params['action'], $params);
                $data = [
                    'success' => true,
                    'callback' => 'reloadObject'
                ];
            } catch (\Exception $e) {
                $data = [
                    'success' => false,
                    'message' => 'error performing action on this element',
                    'reason' => $e->getMessage()
                ];
            }
        } else {
            $data = [
                'success' => false,
                'message' => 'error validating the action on this element, element cannot peform this action',
                'reason' => $manager->getError()
            ];
        }


        $this->_helper->json($data, true);
    }


    /**
     * Returns a new workflow manager for the current element
     * @return Workflow\Manager
     */
    protected function getWorkflowManager()
    {
        if (!$this->manager) {
            $this->manager = Workflow\Manager\Factory::getManager($this->element, $this->user);
        }

        return $this->manager;
    }


    /**
     * Returns a Decorator for the Workflow
     * @return Workflow\Decorator
     */
    protected function getDecorator()
    {
        if ($this->decorator) {
            return $this->decorator;
        }
        $this->decorator = new Workflow\Decorator();

        return $this->decorator;
    }

    /**
     * @param  Document|Asset|ConcreteObject $element
     * @return Document|Asset|ConcreteObject
     */
    protected function getLatestVersion($element)
    {

        //TODO move this maybe to a service method, since this is also used in ObjectController and DocumentControllers
        if ($element instanceof Document) {
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();
                if ($latestDoc instanceof Document) {
                    $element = $latestDoc;
                    $element->setModificationDate($element->getModificationDate());
                }
            }
        }

        if ($element instanceof Object\Concrete) {
            $modificationDate = $element->getModificationDate();
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestObj = $latestVersion->loadData();
                if ($latestObj instanceof ConcreteObject) {
                    $element = $latestObj;
                    $element->setModificationDate($modificationDate);
                }
            }
        }

        return $element;
    }
}
