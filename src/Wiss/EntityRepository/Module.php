<?php

namespace Wiss\EntityRepository;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;

/**
 * 
 */
class Module extends \Doctrine\ORM\EntityRepository
{		
	
    /**
     *
     * @param \Wiss\Entity\Module $module
     */
    public function generate(\Wiss\Entity\Module $module) 
	{   
        $this->generateFolderStructure($module);
        $this->generateConfig($module);
        $this->generateZfModule($module);
    }
    
    /**
     *
     * @param \Wiss\Entity\Module $module
     */
    public function generateFolderStructure(\Wiss\Entity\Module $module)
    {
        // Set the base module folder
        $folder = 'module/' . $module->getName();
        
        // Build the view folder with dashes
        $filter = new \Zend\Filter\Word\CamelCaseToDash();
        $viewFolder = $filter->filter($folder);
        
        @mkdir($folder);
        @mkdir($folder . '/config');
        @mkdir($folder . '/language');
        @mkdir($folder . '/src');
        @mkdir($folder . '/src/' . $folder);
        @mkdir($folder . '/src/' . $folder . '/Controller');
        @mkdir($folder . '/src/' . $folder . '/Entity');
        @mkdir($folder . '/src/' . $folder . '/Form');
        @mkdir($folder . '/view');
        @mkdir($folder . '/view/' . $viewFolder);        
    }
    
    /**
     *
     * @param \Wiss\Entity\Module $module
     */
    public function generateConfig(\Wiss\Entity\Module $module)
    {
        // Get the basic information
        $folder = 'module/' . $module->getName() . '/config';
        $filename = $folder . '/module.config.php';
        $driverName = 'driver_module_' . $module->getId();
        
        $config = array(
            'doctrine' => array(
                'driver' => array(
                    $driverName => array(
                        'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                        'cache' => 'array',
                        'paths' => array($module->getName() . '/Entity')
                    ),
                    'orm_default' => array(
                        'drivers' => array(
                            $module->getName() => $driverName
                        )
                    )
                ),
            ),
        );
        
        // Write the config to disk
        $writer = new \Zend\Config\Writer\PhpArray();
        $writer->toFile($filename, $config);
    }
    
    /**
     *
     * @param \Wiss\Entity\Module $module
     */
    public function generateZfModule(\Wiss\Entity\Module $module)
    {
        // Get the basic information
        $filename = 'module/' . $module->getName() . '/Module.php';        
        $fileData = array(
            'filename' => $filename,
            'namespace' => $module->getName(),
            'uses' => array(
                array('Zend\ModuleManager\ModuleManagerInterface'),
                array('Zend\EventManager\EventInterface'),
                array('Zend\ModuleManager\Feature\InitProviderInterface'),
                array('Zend\ModuleManager\Feature\BootstrapListenerInterface'),
                array('Zend\ModuleManager\Feature\ConfigProviderInterface'),
                array('Zend\ModuleManager\Feature\ServiceProviderInterface'),
                array('Zend\ModuleManager\Feature\ControllerProviderInterface'),
                array('Zend\ModuleManager\Feature\ControllerPluginProviderInterface'),
                array('Zend\ModuleManager\Feature\ViewHelperProviderInterface'),
            ),
            'class' => array(
                'name' => 'Module',
                'implementedinterfaces' => array(
                    'InitProviderInterface',
                    'BootstrapListenerInterface',
                    'ConfigProviderInterface',
                    'ServiceProviderInterface',
                    'ControllerProviderInterface',
                    'ControllerPluginProviderInterface',
                    'ViewHelperProviderInterface',
                ),
                'methods' => array(
                    array('init', array( new ParameterGenerator('manager', 'ModuleManagerInterface'))),
                    array('onBootstrap', array( new ParameterGenerator('e', 'EventInterface'))),
                    array('getConfig', array(), MethodGenerator::FLAG_PUBLIC, 'return include __DIR__ . \'/config/module.config.php\';'),
                    array('getServiceConfig', array(), MethodGenerator::FLAG_PUBLIC, 'return array();'),
                    array('getControllerConfig', array(), MethodGenerator::FLAG_PUBLIC, 'return array();'),
                    array('getControllerPluginConfig', array(), MethodGenerator::FLAG_PUBLIC, 'return array();'),
                    array('getViewHelperConfig', array(), MethodGenerator::FLAG_PUBLIC, 'return array();'),
                )
            ),
        );
         
        // Write the data to disk
        $generator = FileGenerator::fromArray($fileData);
        $generator->write();
    }
}
