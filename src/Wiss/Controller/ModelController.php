<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Wiss\Controller;

use Wiss\Entity\Model;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Code\Scanner\FileScanner;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Code\Annotation\Parser;
use Zend\Code\Annotation\AnnotationManager;
use Zend\Code\Reflection\ClassReflection;
use Doctrine\ORM\EntityManager;

class ModelController extends AbstractActionController {

    /**
     *
     * @param EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * Shows a list of all models that are currently installed
     *
     */
    public function indexAction() {
        $models = $this->getInstalledModels();
        return compact('models');
    }

    /**
     *
     * @return array 
     */
    public function uninstalledAction() 
	{
        // Get the scanned and installed models
        $scanned = $this->getScannedEntities();
        $models = $this->getInstalledModels();

        // Unset each model that already is installed
        foreach ($models as $model) {
            unset($scanned[$model->getEntityClass()]);
        }

        return compact('scanned');
    }
    
    /**
     *
     * @return array 
     */
    public function createAction() 
	{
        $data = array();
		$em = $this->getEntityManager();
		$repo = $em->getRepository('Wiss\Entity\Model');
				
		$class = $this->buildClassNameFromUrlParam();
		$title = $repo->buildTitleFromClass($class);

        if($class) {            
			
			$model = $repo->findOneByEntityClass($class);
		
			// Return if a model already exists
			if ($model) {

				// Show a flash message
				$this->flashMessenger()->addMessage('The model is already installed');

				// Redirect
				$this->redirect()->toRoute('wiss/content/' . $model->getSlug());
				return false;
			}
			
            // Get data from entity annotations
            $data = $this->getDataFromAnnotations($class);
            $data += array(
                'title' => $title,
                'entity_class' => $class,
            );
        }
		else {
			$model = new Model;
		}

        // Create the form
        $form = $this->getServiceLocator()->get('Wiss\Form\Model\Properties');   
		$form->prepareElements();
		$form->bind($model);
		$form->setData($data);

        if ($this->getRequest()->isPost()) {
			
            $form->setData($this->getRequest()->getPost());

            if ($form->isValid()) {

                // Make the model title camelCased
                $filter = new \Zend\Filter\Word\SeparatorToCamelCase();
                $filter->setSeparator(' ');
                $camelCasedTitle = $filter->filter($model->getTitle());
                                
                // Get the model
                $model = $form->getData();
                $module = $model->getModule()->getName();
                $model->setEntityClass($module . '\Entity\\' . $camelCasedTitle);
                $model->setControllerClass($module . '\Controller\\' . $camelCasedTitle);
                $model->setFormClass($module . '\Form\\' . $camelCasedTitle);                
                $em->persist($model);
                $em->flush();

                // Create new routes and navigation				
                $repo->generateRoutes($model);
                $repo->generateNavigation($model);
                
                // Save the newly created model
                $em->persist($model);
                $em->flush();
                
                // Show a flash message
                $this->flashMessenger()->addMessage('The model is now created');

                // Redirect
                $this->redirect()->toRoute('wiss/model/generate', array(
                    'slug' => $model->getSlug()
                ));
            }
        }

        return compact('title', 'form');
    }

    /**
     *
     * @return array 
     */
    public function propertiesAction() 
	{
		// Get the model
		$em = $this->getEntityManager();
		$repo = $em->getRepository('Wiss\Entity\Model');				
		$model = $repo->findOneBy(array(
            'slug' => $this->params('slug'),
        ));

        // Create the form
        $form = $this->getServiceLocator()->get('Wiss\Form\Model\Properties');   
		$form->setName('model');
		$form->prepareElements(array());
		$form->bind($model);

        if ($this->getRequest()->isPost()) {
			
            $form->setData($this->getRequest()->getPost());

            if ($form->isValid()) {
								
                // Save the model
                $em->persist($form->getData());

                // Build the config
                $repo->generateRoutes($model);
                $repo->generateNavigation($model);
        
                // Show a flash message
                $this->flashMessenger()->addMessage('The model properties are updated');

                // Redirect
                $this->redirect()->toRoute('wiss/model/properties', array(
                    'slug' => $model->getSlug()
                ));
            }
        }

        return compact('form', 'model');
    }

    /**
     *
     * @return array 
     */
    public function elementsAction() 
	{
		// Get the model
		$em = $this->getEntityManager();
		$repo = $em->getRepository('Wiss\Entity\Model');		
		$model = $repo->findOneBy(array(
            'slug' => $this->params('slug')
        ));
		
        return compact('model');
    }
	
    /**
     * 
     * @return boolean 
     */
    public function generateAction()
    {    
		// Get the model
		$em = $this->getEntityManager();
		$repo = $em->getRepository('Wiss\Entity\Model');		
		$model = $repo->findOneBy(array(
            'slug' => $this->params('slug')
        ));
        
        // Generate the model parts
        $repo->generateEntity($model);
        $repo->generateController($model);
        $repo->generateForm($model);        
        
        // Show a flash message
        $this->flashMessenger()->addMessage('The model is succesfully generated!');

        // Redirect
        $this->redirect()->toRoute('wiss/model/elements', array(
            'slug' => $model->getSlug()
        ));	
        
        return false;
    }
    
	/**
     * Get the class from the url params
	 * 
	 * @return string
	 */
	public function buildClassNameFromUrlParam()
	{		
        return str_replace('-', '\\', $this->params('class'));
	}
    
    /**
     * Read some useful information from the annotations regarding
     * list overviews
     *
     * @param string $class
     * @return array 
     */
    public function getDataFromAnnotations($class) 
	{
        // Build an annotation parser to read the annotations
        $parser = new Parser\DoctrineAnnotationParser();
        $parser->registerAnnotation('Wiss\Annotation\Overview');

        // Add the parser to the annotation manager
        $annotationManager = new AnnotationManager();
        $annotationManager->attach($parser);

        // Use reflection to inspect the class for annotations
        $reflection = new ClassReflection($class);
        $annotations = $reflection->getAnnotations($annotationManager);

        // Walk each found annotations
        foreach ($annotations as $annotation) {

            // Add the overview title fiel
            if ($annotation instanceof \Wiss\Annotation\Overview) {
                return array(
                    'title_field' => $annotation->getTitleField()
                );
            }
        }

        return array();
    }

    /**
     *
     * @return Doctrine\ORM\Collection
     */
    public function getInstalledModels() 
	{
        return $this->getEntityManager()->getRepository('Wiss\Entity\Model')->findAll();
    }

    /**
     *
     * @return array
     */
    public function getScannedEntities() 
	{
        $em = $this->getEntityManager();
        $config = $this->getServiceLocator()->get('applicationconfig');
        $paths = $config['module_listener_options']['module_paths'];
        $drivers = $em->getConfiguration()->getMetadataDriverImpl()->getDrivers();
        $entities = array();

        // Walk thru all found paths to search for models
        foreach ($paths as $pathName => $basepath) {

            // Do not show all the Wiss models in the list. It can be confusing.
            // Only show the models found in the website application.
            if ($pathName === 'Wiss') {
                continue;
            }

            // Each path can have folders that are equal to the ones given
            // in the Doctrine drivers. See if there are folders within
            // the current path that match with a driver folder.
            foreach ($drivers as $namespace => $driver) {

                foreach ($driver->getPaths() as $path) {

                    // Build the path to the file
                    $filePattern = '%s/%s/src/%s';
                    $file = sprintf($filePattern, $basepath, $namespace, $path);
                    $entities += $this->getEntitiesByPath($file);
                }
            }
        }

        return $entities;
    }

    /**
     * 
     * @param string $path
     * @return array
     */
    public function getEntitiesByPath($path) {
        $entities = array();

        // Check if the file exists
        if (!file_exists($path)) {
            return $entities;
        }

        // Walk each file in the directory to see if there is
        // a valid entity
        $directory = new \DirectoryIterator($path);
        foreach ($directory as $file) {

            // Only use real files
            if ($file->isDot() || $file->isDir()) {
                continue;
            }

            // Start a file scanner, to check for classes inside the file
            $scanner = new FileScanner($file->getPathname());

            // Check the file for classes                            
            foreach ($scanner->getClassNames() as $class) {

                try {

                    // See if we can build an entity without throwing an exception.
                    // If no exception is thrown, then we have a valid entity
                    $entity = $this->getEntityManager()->getRepository($class);
                    $entities[$class] = $entity;
                } catch (\Exception $e) {
                    // Just skip to the next
                }
            }
        }

        return $entities;
    }

    /**
     *
     * @param EntityManager $entityManager 
     */
    public function setEntityManager(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     *
     * @return EntityManager 
     */
    public function getEntityManager() {
        return $this->entityManager;
    }

}
