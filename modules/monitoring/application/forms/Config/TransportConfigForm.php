<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config;

use Icinga\Data\ConfigObject;
use Icinga\Module\Monitoring\Command\Transport\CommandTransport;
use Icinga\Module\Monitoring\Exception\CommandTransportException;
use InvalidArgumentException;
use Icinga\Application\Platform;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfigForm;
use Icinga\Module\Monitoring\Command\Transport\ApiCommandTransport;
use Icinga\Module\Monitoring\Command\Transport\LocalCommandFile;
use Icinga\Module\Monitoring\Command\Transport\RemoteCommandFile;
use Icinga\Module\Monitoring\Forms\Config\Transport\ApiTransportForm;
use Icinga\Module\Monitoring\Forms\Config\Transport\LocalTransportForm;
use Icinga\Module\Monitoring\Forms\Config\Transport\RemoteTransportForm;

/**
 * Form for managing command transports
 */
class TransportConfigForm extends ConfigForm
{
    /**
     * The transport to load when displaying the form for the first time
     *
     * @var string
     */
    protected $transportToLoad;

    /**
     * The names of all available Icinga instances
     *
     * @var array
     */
    protected $instanceNames;

    /**
     * @var bool
     */
    protected $validatePartial = true;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_command_transports');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * Set the names of all available Icinga instances
     *
     * @param   array   $names
     *
     * @return  $this
     */
    public function setInstanceNames(array $names)
    {
        $this->instanceNames = $names;
        return $this;
    }

    /**
     * Return the names of all available Icinga instances
     *
     * @return  array
     */
    public function getInstanceNames()
    {
        return $this->instanceNames ?: array();
    }

    /**
     * Return a form object for the given transport type
     *
     * @param   string  $type               The transport type for which to return a form
     *
     * @return  \Icinga\Web\Form
     *
     * @throws  InvalidArgumentException    In case the given transport type is invalid
     */
    public function getTransportForm($type)
    {
        switch (strtolower($type)) {
            case LocalCommandFile::TRANSPORT:
                return new LocalTransportForm();
            case RemoteCommandFile::TRANSPORT:
                return new RemoteTransportForm();
            case ApiCommandTransport::TRANSPORT:
                return new ApiTransportForm();
            default:
                throw new InvalidArgumentException(
                    sprintf($this->translate('Invalid command transport type "%s" given'), $type)
                );
        }
    }

    /**
     * Populate the form with the given transport's config
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no transport with the given name is found
     */
    public function load($name)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No command transport called "%s" found', $name);
        }

        $this->transportToLoad = $name;
        return $this;
    }

    /**
     * Add a new command transport
     *
     * The transport to add is identified by the array-key `name'.
     *
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    In case $data does not contain a transport name
     * @throws  IcingaException             In case a transport with the same name already exists
     */
    public function add(array $data)
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Key \'name\' missing');
        }

        $transportName = $data['name'];
        if ($this->config->hasSection($transportName)) {
            throw new IcingaException(
                $this->translate('A command transport with the name "%s" does already exist'),
                $transportName
            );
        }

        unset($data['name']);
        $this->config->setSection($transportName, $data);
        return $this;
    }

    /**
     * Edit an existing command transport
     *
     * @param   string  $name
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no transport with the given name is found
     */
    public function edit($name, array $data)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No command transport called "%s" found', $name);
        }

        $transportConfig = $this->config->getSection($name);
        if (isset($data['name'])) {
            if ($data['name'] !== $name) {
                $this->config->removeSection($name);
                $name = $data['name'];
            }

            unset($data['name']);
        }

        $transportConfig->merge($data);
        $this->config->setSection($name, $transportConfig);
        return $this;
    }

    /**
     * Remove a command transport
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function delete($name)
    {
        $this->config->removeSection($name);
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
        $instanceNames = $this->getInstanceNames();
        if (count($instanceNames) > 1) {
            $options = array('none' => $this->translate('None', 'command transport instance association'));
            $this->addElement(
                'select',
                'instance',
                array(
                    'label'         => $this->translate('Instance Link'),
                    'description'   => $this->translate(
                        'The name of the Icinga instance this transport should exclusively transfer commands to.'
                    ),
                    'multiOptions'  => array_merge($options, array_combine($instanceNames, $instanceNames))
                )
            );
        }

        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Transport Name'),
                'description'   => $this->translate(
                    'The name of this command transport that is used to differentiate it from others'
                )
            )
        );

        $transportTypes = array(
            ApiCommandTransport::TRANSPORT  => $this->translate('Icinga 2 API'),
            LocalCommandFile::TRANSPORT     => $this->translate('Local Command File'),
            RemoteCommandFile::TRANSPORT    => $this->translate('Remote Command File')
        );
        if (! Platform::extensionLoaded('curl')) {
            unset($transportTypes[ApiCommandTransport::TRANSPORT]);
        }

        $transportType = isset($formData['transport']) ? $formData['transport'] : null;
        if ($transportType === null) {
            $transportType = key($transportTypes);
        }

        $this->addElements(array(
            array(
                'select',
                'transport',
                array(
                    'required'      => true,
                    'autosubmit'    => true,
                    'label'         => $this->translate('Transport Type'),
                    'multiOptions'  => $transportTypes
                )
            )
        ));

        $this->addSubForm($this->getTransportForm($transportType)->create($formData), 'transport_form');
    }

    /**
     * Add a submit button to this form and one to manually validate the configuration
     *
     * Calls parent::addSubmitButton() to add the submit button.
     *
     * @return  $this
     */
    public function addSubmitButton()
    {
        parent::addSubmitButton();

        if ($this->getSubForm('transport_form') instanceof ApiTransportForm) {
            $this->addElement(
                'submit',
                'transport_validation',
                array(
                    'ignore' => true,
                    'label' => $this->translate('Validate Configuration'),
                    'data-progress-label' => $this->translate('Validation In Progress'),
                    'decorators' => array('ViewHelper')
                )
            );

            $this->setAttrib('data-progress-element', 'transport-progress');
            $this->addElement(
                'note',
                'transport-progress',
                array(
                    'decorators' => array(
                        'ViewHelper',
                        array('Spinner', array('id' => 'transport-progress'))
                    )
                )
            );

            $btnSubmit = $this->getElement('btn_submit');
            $elements = array('transport_validation', 'transport-progress');
            if ($btnSubmit !== null) {
                // In the setup wizard $this is being used as a subform which doesn't have a submit button.
                $btnSubmit->setDecorators(array('ViewHelper'));
                array_unshift($elements, 'btn_submit');
            }
            $this->addDisplayGroup(
                $elements,
                'submit_validation',
                array(
                    'decorators' => array(
                        'FormElements',
                        array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                    )
                )
            );
        }

        return $this;
    }

    /**
     * Populate the configuration of the transport to load
     */
    public function onRequest()
    {
        if ($this->transportToLoad) {
            $data = $this->config->getSection($this->transportToLoad)->toArray();
            $data['name'] = $this->transportToLoad;
            $this->populate($data);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isValidPartial(array $formData)
    {
        $isValidPartial =  parent::isValidPartial($formData);

        $transportValidation = $this->getElement('transport_validation');
        if ($transportValidation !== null && $transportValidation->isChecked() && $this->isValid($formData)) {
            $this->info($this->translate('The configuration has been successfully validated.'));
        }

        return $isValidPartial;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }

        if (! ($this->getElement('transport_validation') === null || (
            $this->isSubmitted() && isset($formData['force_creation']) && $formData['force_creation'])
        )) {
            try {
                CommandTransport::createTransport(new ConfigObject($this->getValues()))->probe();
            } catch (CommandTransportException $e) {
                $this->error(sprintf(
                    $this->translate('Failed to successfully validate the configuration: %s'),
                    $e->getMessage()
                ));

                $this->addElement(
                    'checkbox',
                    'force_creation',
                    array(
                        'order'         => 0,
                        'ignore'        => true,
                        'label'         => $this->translate('Force Changes'),
                        'description'   => $this->translate(
                            'Check this box to enforce changes without connectivity validation'
                        )
                    )
                );

                return false;
            }
        }

        return true;
    }
}
