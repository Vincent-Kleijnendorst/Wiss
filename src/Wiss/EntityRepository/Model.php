<?php

namespace Wiss\EntityRepository;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Gedmo\Sluggable\Util\Urlizer;
use Wiss\Form\ModelExport as ExportForm;

/**
 * 
 */
class Model extends \Doctrine\ORM\EntityRepository
{		
    /**
     *
     * @param array $data 
     * @return Model
     */
    public function createFromArray(Array $data)
	{
        $em = $this->getEntityManager();
        // Create a new model
        $model = new \Wiss\Entity\Model;
        $model->setTitle($data['title']);
        $model->setEntityClass($data['entity_class']);
        $model->setTitleField($data['title_field']);
		$model->setFormConfig($data['elements']);
		
        // Save this new model
        $em->persist($model);
        $em->flush();

        return $model;
    }
	
    /**
     *
     * @param \Wiss\Entity\Model $model
     */
    public function generateNavigation(\Wiss\Entity\Model $model) 
	{
        $namespace = $model->getSlug();
        
        // Build the config, starting from navigation
        $config['navigation'] = array(
            $namespace => array(
                'label' => $model->getTitle(),
                'route' => $namespace,
                'pages' => array(
                    'create' => array(
                        'label' => 'Create',
                        'route' => $namespace . '/create',
                    ),
                    'edit' => array(
                        'label' => 'Edit',
                        'route' => $namespace . '/edit',
                    ),
                )
            )
        );

        // Import the config thru the Navigation entity repository
        $em = $this->getEntityManager();
        $repo = $em->getRepository('Wiss\Entity\Navigation');
        $repo->import($config);
		$repo->export();
    }
	
	
    /**
     *
     * @param \Wiss\Entity\Model $model 
     */
    public function generateRoutes(\Wiss\Entity\Model $model) 
	{
        $namespace = $model->getSlug();
        
        // Build the config, starting from router.routes
        $config['router']['routes'] = array(
            $namespace => array(
                'type' => 'Literal',
                'may_terminate' => true,
                'options' => array(
                    'route' => '/' . $namespace,
                    'defaults' => array(
                        'controller' => $model->getControllerClass(),
                        'action' => 'index',
                    ),
                ),
                'child_routes' => array(
                    'create' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/create',
                            'defaults' => array(
                                'action' => 'create',
                            ),
                        )
                    ),
                    'edit' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/edit/:id',
                            'defaults' => array(
                                'action' => 'edit',
                            ),
                            'constraints' => array(
                                'id' => '[0-9]+',
                            ),
                        )
                    ),
                    'delete' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/delete/:id',
                            'defaults' => array(
                                'action' => 'delete',
                            ),
                            'constraints' => array(
                                'id' => '[0-9]+',
                            ),
                        )
                    )
                )
            )
        );

        // Import the config thru the Page entity repository
        $em = $this->getEntityManager();
        $repo = $em->getRepository('Wiss\Entity\Route');
        $repo->import($config);		
		$repo->export();	
    }
	
	

    /**
     *
     * @param ExportForm $form
     * @return string 
     */
    public function generateController(ExportForm $form) 
	{		
		$model = $form->getModel();
        $namespace = 'Application\Controller';
        $className = $form->get('controller_class')->getValue();
		$filename = $form->get('controller_path')->getValue();

        $fileData = array(
            'filename' => $filename,
            'namespace' => $namespace,
            'uses' => array(
                array('Wiss\Controller\CrudController', 'EntityController'),
            ),
            'class' => array(
                'name' => $className . 'Controller',
                'extendedclass' => 'EntityController',
                'properties' => array(
                    array('modelName', $model->getSlug(), PropertyGenerator::FLAG_PROTECTED),
                )
            ),
        );

        $generator = FileGenerator::fromArray($fileData);
        $generator->write();

        return $namespace . '\\' . $className;
    }
		
    /**
     * 
     * @param ExportForm $form
     * @return string
     */
    public function generateForm(ExportForm $form) 
	{
        $model = $form->getModel();
        $elementData = $model->getFormConfig();
        $className = $form->get('form_class')->getValue();

        // Create the body for in the __construct method
        $body = sprintf('parent::__construct(\'%s\');', $className) . PHP_EOL . PHP_EOL;
        $body .= '$this->setHydrator(new ClassMethodsHydrator());' . PHP_EOL;
        $body .= '$this->setAttribute(\'class\', \'form-horizontal\');' . PHP_EOL . PHP_EOL;

        // Add the elements 
        foreach ($elementData as $name => $element) {

			// Decode the element config
			$vars = urldecode($element['configuration']);
			parse_str($vars, $output);
			
			// Check if the element has a type or config, otherwis
			// there is nothing to do
            if (!$element['type'] || !isset($output['element-config'])) {
                continue;
            }
			
			$config = $output['element-config'];

            // Create the element method
            $body .= '// ' . $name . PHP_EOL;
            $body .= '$this->add(array(' . PHP_EOL;
            $body .= sprintf('  \'name\' => \'%s\',', $name) . PHP_EOL;
            $body .= sprintf('  \'type\' => \'%s\',', $element['type']) . PHP_EOL;
            $body .= '	\'attributes\' => array(' . PHP_EOL;
            $body .= sprintf('    \'label\' => \'%s\',', $config['label']) . PHP_EOL;
            $body .= ')));' . PHP_EOL . PHP_EOL;
        }

        // Create the submit method
        $body .= '// submit' . PHP_EOL;
        $body .= '$this->add(array(' . PHP_EOL;
        $body .= sprintf('  \'name\' => \'%s\',', 'submit') . PHP_EOL;
        $body .= sprintf('  \'type\' => \'%s\',', 'Zend\Form\Element\Submit') . PHP_EOL;
        $body .= '	\'attributes\' => array(' . PHP_EOL;
        $body .= sprintf('    \'value\' => \'%s\',', 'Save') . PHP_EOL;
        $body .= sprintf('    \'class\' => \'%s\',', 'btn btn-primary') . PHP_EOL;
        $body .= ')));' . PHP_EOL . PHP_EOL;

        // Set the names for file generation
        $namespace = 'Application\Form';
        $filename = $form->get('form_path')->getValue();

					
        // Build the file holding the php class
        $fileData = array(
            'filename' => $filename,
            'namespace' => $namespace,
            'uses' => array(
                array('Zend\Form\Form'),
                array('Zend\StdLib\Hydrator\ClassMethods', 'ClassMethodsHydrator'),
            ),
            'class' => array(
                'name' => $className,
                'extendedclass' => 'Form',
                'methods' => array(
                    array(
                        'name' => '__construct',
                        'parameters' => array(),
                        'flags' => null,
                        'body' => $body,
                    )
                )
            ),
        );

        // Generate the file and save it to disk
        $generator = FileGenerator::fromArray($fileData);
        $generator->write();

        // Return the classname to be used later
        return $namespace . '\\' . $className;
    }
	
	/**
	 * 
	 * @param string $class
	 * @return string
	 */
	public function buildTitleFromClass($class)
	{
        // Get the title based on the class
        $title = explode('\\', $class);
        $title = end($title);
		return $title;
	}
	
	/**
	 * 
	 * @param string $entityClass
	 * @return Wiss\Entity\Model
	 */
	public function findOneByEntityClass($entityClass)
	{		
        // Find the model with this class
        return $this->findOneBy(array(
            'entityClass' => $entityClass,
		));
	}
	
	/**
	 * 
	 * @param string $slug
	 * @return Wiss\Entity\Model
	 */
	public function findOneBySlug($slug)
	{		
        // Find the model with this class
        return $this->findOneBy(array(
            'slug' => $slug
		));
	}
	
}
