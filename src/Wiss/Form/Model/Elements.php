<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Wiss\Form\Model;

use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Form\Form;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Wiss\Entity\Model;

class Elements extends Form implements ServiceLocatorAwareInterface
{	
    protected $serviceLocator;

    /**
	 * 
	 * @param Model $model
	 */
    public function prepareElements()
    {                		
		$this->setAttribute('class', 'form-horizontal');
			
						
		$elements = new Fieldset('elements');
		
		$inputFilter = $this->getInputFilter();
		$inputFilter->add(new Input('elements', array(
			'required' => true,
		)));
                        
        // Get the value options from the service manager config
        $config = $this->getServiceLocator()->get('config');
        $valueOptions = $config['element-config-forms'];
			      
		// Add the select field with the available elements
		$select = new Element\Select('type');
		$select->setLabel($field['fieldName']);
		$select->setValueOptions(array('' => 'No element assigned yet...') + $valueOptions); 
		$select->setAttributes(array(
			'class' => 'form-class',
		));
		$this->add($select);
			    
		   
        
		// Submit
		$submit = new Element('submit');
		$submit->setAttributes(array(
			'type'  => 'submit',
			'value' => 'Continue',
			'class' => 'btn btn-primary btn-large',
		));

		$this->add($submit);

		$this->setInputFilter($inputFilter);
	
				
	}
	
	public function getServiceLocator() {
		return $this->serviceLocator;
	}

	public function setServiceLocator(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator) {
		$this->serviceLocator = $serviceLocator;
	}

}
