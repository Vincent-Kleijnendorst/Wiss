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

class ModelController extends AbstractActionController
{
	protected $entityManager;
	
    public function indexAction()
    {
		$models = $this->getEntityManager()->getRepository('Wiss\Entity\Model')->findAll();
		
		return compact('models');
    }
		
	public function uninstalledAction()
	{
		$scanned = $this->getScannedEntities();
		$models = $this->getInstalledModels();
		
		// Unset each model that already is installed
		foreach($models as $model) {
			unset($scanned[get_class($model)]);
		}
		
		return compact('scanned');
	}
	
	/**
	 *
	 * @return Doctrine\ORM\Collection
	 */
	public function getInstalledModels()
	{		
		$models = $this->getEntityManager()->getRepository('Wiss\Entity\Model')->findAll();
		return $models;
	}
	
	public function getScannedEntities()
	{
		$config = $this->getServiceLocator()->get('applicationconfig');
		$paths = $config['module_listener_options']['module_paths'];
		$drivers = $this->getEntityManager()->getConfiguration()->getMetadataDriverImpl()->getDrivers();
		$entities = array();
							
		foreach($paths as $basepath) {
					
			foreach($drivers as $namespace => $driver) {

				foreach($driver->getPaths() as $path) {

					$filePattern = '%s/%s/src/%s%s';
					$file = sprintf($filePattern, $basepath, $namespace, $namespace, $path);
					
					if(!file_exists($file)) {
						continue;
					}
					
					$directory = new \DirectoryIterator($file);
					foreach($directory as $file) {
						
						if($file->isDot() || $file->isDir()) {
							continue;
						}
									
						try {							
							
							$scanner = new \Zend\Code\Scanner\FileScanner($file->getPathname());
							foreach($scanner->getClassNames() as $class) {
								
								try {
									$entity = $this->getEntityManager()->getRepository($class);
									$entities[$class] = $entity;
								}
								catch(\Exception $e) {
									
								}
							}
							
						}
						catch(\Exception $e) {
						}

					}
				}
				
			}
			
		}
		
		return $entities;
	}
	
	public function listAction()
	{
	}
		
	public function editAction()
	{
		$repo = $this->getEntityManager()->getRepository('Wiss\Entity\Model');
		$model = $repo->findOneBy(array('slug' => $this->params('name')));
		
		$entityClass = $model->getEntityClass();
		$entity = $this->getEntityManager()->find($entityClass, $this->params('id'));
		
		
		$listener = new \Wiss\Form\Annotation\ElementAnnotationsListener;
		$builder = new AnnotationBuilder();
//		$builder->getEventManager()->attachAggregate($listener);
		
        $parser = new Parser\DoctrineAnnotationParser();
		$parser->registerAnnotation('Wiss\Form\Mapping\Text');
		$builder->getAnnotationManager()->attach($parser);
		
		
		$form = $builder->createForm($entityClass);
//		$form = $builder->createForm('Wiss\Entity\User');
		\Zend\Debug\Debug::dump($form); exit;
							
		return compact('model', 'entity', 'form');
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
}
