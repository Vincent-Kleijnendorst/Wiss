<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Wiss\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Code\Annotation\Parser;
use Zend\Code\Annotation\AnnotationManager;
use Zend\Code\Reflection\ClassReflection;
use Zend\StdLib\Hydrator\ClassMethods as ClassMethodsHydrator;

class CrudController extends AbstractActionController
{
	protected $modelName;
	
	protected $entityManager;
	
    public function indexAction()
	{
		$repo = $this->getEntityManager()->getRepository('Wiss\Entity\Model');
		$model = $repo->findOneBy(array('slug' => $this->getModelName()));
		
		$entityClass = $model->getEntityClass();
		$entities = $this->getEntityManager()->getRepository($entityClass)->findAll();
		
		$labelGetter = 'get' . ucfirst($model->getTitleField());
		$params = compact('model', 'entities', 'labelGetter');
		$viewModel = new ViewModel($params);
		$viewModel->setTemplate('wiss/crud/index');
		
		return $viewModel;
	}
		
	public function editAction()
	{
		$em = $this->getEntityManager();
		$repo = $em->getRepository('Wiss\Entity\Model');
		$model = $repo->findOneBy(array('slug' => $this->params('name')));
		
		$entityClass = $model->getEntityClass();
		$entity = $this->getEntityManager()->find($entityClass, $this->params('id'));
		
		$class = $model->getFormClass();
		$form = new $class();
		$form->bind($entity);
		
		if($this->getRequest()->isPost()) {
			
			$form->setData($this->getRequest()->getPost());
			if($form->isValid()) {
				
				// Save changes
				$em->persist($form->getData());
				$em->flush();
				
				// Show a flash message
				$this->flashMessenger()->addMessage('Saved changes succesfully');
				
				// Redirect
				$this->redirect()->toRoute('crud', array(
					'name' => $model->getSlug(),
				));
			}
		}
							
		return compact('model', 'entity', 'form');
	}
	
	/**
	 *
	 * @param string $entityClass
	 * @return \Zend\Form\Form 
	 */
	public function buildForm($entityClass)
	{		
		$listener = new \Wiss\Form\Annotation\ElementAnnotationsListener;
		$builder = new AnnotationBuilder();
		$builder->getEventManager()->attachAggregate($listener);
		
        $parser = new Parser\DoctrineAnnotationParser();
		$parser->registerAnnotation('Wiss\Form\Mapping\Text');
		$parser->registerAnnotation('Wiss\Form\Mapping\Textarea');
		$parser->registerAnnotation('desc');
		
		$annotationManager = $builder->getAnnotationManager();
		$annotationManager->attach($parser);
				
		$form = $builder->createForm($entityClass);
		
		$form->add(array(
			'name' => 'submit',
			'attributes' => array(
				'type' => 'submit',
				'value' => 'Save',
				'class' => 'btn btn-primary btn-large',
			)
		));
		
		
		return $form;
	}
			
	/**
	 *
	 * @param \Doctrine\ORM\EntityManager $entityManager 
	 */
	public function setEntityManager(\Doctrine\ORM\EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}
	
	/**
	 *
	 * @return type 
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}
	
	public function getModelName() {
		return $this->modelName;
	}

	public function setModelName($modelName) {
		$this->modelName = $modelName;
	}


}
