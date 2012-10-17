<?php

namespace Wiss\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ModelElement
{
	/**
	 * 
	 * @ORM\ID
	 * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
	 */
	protected $id;

	/**
	 * 
     * @ORM\Column
	 */
	protected $name;
	
	/**
	 *
     * @ORM\Column(type="array")
	 */
	protected $configuration;
	
	/**
	 *
     * @ORM\ManyToOne(targetEntity="Model", inversedBy="elements")
	 */
	protected $model;
	
	
	public function getId() 
	{
		return $this->id;
	}

	public function getName() 
	{
		return $this->name;
	}

	public function setName($name) 
	{
		$this->name = $name;
	}

	public function getConfiguration() 
	{
		return $this->configuration;
	}

	public function setConfiguration($configuration) 
	{
		$this->configuration = $configuration;
	}
	
	public function getModel() {
		return $this->model;
	}

	public function setModel(Model $model) {
		$this->model = $model;
	}



}
