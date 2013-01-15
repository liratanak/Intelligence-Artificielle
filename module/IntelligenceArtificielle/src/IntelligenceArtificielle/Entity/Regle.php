<?php

namespace IntelligenceArtificielle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Regle
 *
 * @ORM\Table(name="regle")
 * @ORM\Entity
 */
class Regle
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="proposition", type="string", length=64, nullable=true)
     */
    private $proposition;

    /**
     * @var string
     *
     * @ORM\Column(name="verbe", type="string", length=64, nullable=true)
     */
    private $verbe;

    /**
     * @var boolean
     *
     * @ORM\Column(name="negative", type="boolean", nullable=true)
     */
    private $negative;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="IntelligenceArtificielle\Entity\Regle", mappedBy="conclusion")
     */
    private $premisseOf;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->premisseOf = new \Doctrine\Common\Collections\ArrayCollection();
    }
    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set proposition
     *
     * @param string $proposition
     * @return Regle
     */
    public function setProposition($proposition)
    {
        $this->proposition = $proposition;
    
        return $this;
    }

    /**
     * Get proposition
     *
     * @return string 
     */
    public function getProposition()
    {
        return $this->proposition;
    }

    /**
     * Set verbe
     *
     * @param string $verbe
     * @return Regle
     */
    public function setVerbe($verbe)
    {
        $this->verbe = $verbe;
    
        return $this;
    }

    /**
     * Get verbe
     *
     * @return string 
     */
    public function getVerbe()
    {
        return $this->verbe;
    }

    /**
     * Set negative
     *
     * @param boolean $negative
     * @return Regle
     */
    public function setNegative($negative)
    {
        $this->negative = $negative;
    
        return $this;
    }

    /**
     * Get negative
     *
     * @return boolean 
     */
    public function getNegative()
    {
        return $this->negative;
    }

    /**
     * Add premisseOf
     *
     * @param \IntelligenceArtificielle\Entity\Regle $premisseOf
     * @return Regle
     */
    public function addPremisseOf(\IntelligenceArtificielle\Entity\Regle $premisseOf)
    {
        $this->premisseOf[] = $premisseOf;
    
        return $this;
    }

    /**
     * Remove premisseOf
     *
     * @param \IntelligenceArtificielle\Entity\Regle $premisseOf
     */
    public function removePremisseOf(\IntelligenceArtificielle\Entity\Regle $premisseOf)
    {
        $this->premisseOf->removeElement($premisseOf);
    }

    /**
     * Get premisseOf
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPremisseOf()
    {
        return $this->premisseOf;
    }
}