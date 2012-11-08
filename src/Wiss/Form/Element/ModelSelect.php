<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Wiss\Form\Element;

use Zend\Form\Element;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class ModelSelect extends Element\Select implements ServiceLocatorAwareInterface
{	
    protected $serviceLocator;
    
    protected $modelName;
    protected $modelKey;
    protected $modelLabel;

    public function getModelName() {
        return $this->modelName;
    }

    public function setModelName($modelName) {
        $this->modelName = $modelName;
        
        $em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        $model = $em->getRepository('Wiss\Entity\Model')->findOneBy(array(
            'name' => $modelName,
        ));
        $entities = $em->getRepository($model->getEntityClass())->findAll();
        
        \Zend\Debug\Debug::dump($entities);
        
        $valueOptions = array();
//        foreach($entities as $entity) {
//            $valueOptions
//        }
        $this->setValueOptions($valueOptions);
    }
    
    public function getModelKey() {
        return $this->modelKey;
    }

    public function setModelKey($modelKey) {
        $this->modelKey = $modelKey;
    }

    public function getModelLabel() {
        return $this->modelLabel;
    }

    public function setModelLabel($modelLabel) {
        $this->modelLabel = $modelLabel;
    }

    public function getServiceLocator() {
		return $this->serviceLocator;
	}

	public function setServiceLocator(ServiceLocatorAwareInterface $serviceLocator) {
		$this->serviceLocator = $serviceLocator;
	}
}
