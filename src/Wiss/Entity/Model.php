<?php

namespace Wiss\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Wiss\EntityRepository\Model")
 */
class Model
{
	/**
	 * 
	 * @ORM\ID
	 * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
	 */
	protected $id;

	/**
	 * 
     * @ORM\Column
	 */
	protected $title;
	
	/**
	 *
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=128, unique=false)
	 */
	protected $slug;
	
	/**
	 * 
     * @ORM\Column
	 */
	protected $entityClass;
	
	/**
	 * 
     * @ORM\Column(nullable=true)
	 */
	protected $formClass;
	
	/**
	 * 
     * @ORM\Column(nullable=true)
	 */
	protected $controllerClass;
	
	/**
	 *
	 * @ORM\OneToMany(targetEntity="ModelElement", mappedBy="model")
	 */
	protected $elements;

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getSlug() {
		return $this->slug;
	}

	public function setSlug($slug) {
		$this->slug = $slug;
	}

	public function getEntityClass() {
		return $this->entityClass;
	}

	public function setEntityClass($entityClass) {
		$this->entityClass = $entityClass;
	}

	public function getFormClass() {
		return $this->formClass;
	}

	public function setFormClass($formClass) {
		$this->formClass = $formClass;
	}

	public function getControllerClass() {
		return $this->controllerClass;
	}

	public function setControllerClass($controllerClass) {
		$this->controllerClass = $controllerClass;
	}

	public function getElements() {
		return $this->elements;
	}

	public function setElements($elements) {
		$this->elements = $elements;
	}


}
