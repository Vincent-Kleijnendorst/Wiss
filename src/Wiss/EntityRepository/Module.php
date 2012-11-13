<?php

namespace Wiss\EntityRepository;

use Zend\Code\Generator\FileGenerator;

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
        $config = array();
        
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
            'class' => array(
                'name' => $module->getName(),
            ),
        );
         
        // Write the data to disk
        $generator = FileGenerator::fromArray($fileData);
        $generator->write();
    }
}
