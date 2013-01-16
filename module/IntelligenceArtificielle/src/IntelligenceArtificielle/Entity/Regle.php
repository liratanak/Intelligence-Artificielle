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
     * @var boolean
     *
     * @ORM\Column(name="negative", type="boolean", nullable=true)
     */
    private $negative;

    /**
     * @var string
     *
     * @ORM\Column(name="verbe", type="string", length=64, nullable=true)
     */
    private $verbe;
	/**
	 * @var \Doctrine\Common\Collections\Collection
	 *
	 * @ORM\ManyToMany(targetEntity="IntelligenceArtificielle\Entity\Regle", inversedBy="regle")
	 * @ORM\JoinTable(name="relations",
	 *   joinColumns={
	 *     @ORM\JoinColumn(name="conclusion", referencedColumnName="id")
	 *   },
	 *   inverseJoinColumns={
	 *     @ORM\JoinColumn(name="premiss", referencedColumnName="role_id")
	 *   }
	 * )
	 */
	private $conclusion;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="IntelligenceArtificielle\Entity\Regle", mappedBy="conclusion")
     */
    private $premiss;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->premiss = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add premiss
     *
     * @param \IntelligenceArtificielle\Entity\Regle $premiss
     * @return Regle
     */
    public function addPremis(\IntelligenceArtificielle\Entity\Regle $premiss)
    {
        $this->premiss[] = $premiss;
    
        return $this;
    }

    /**
     * Remove premiss
     *
     * @param \IntelligenceArtificielle\Entity\Regle $premiss
     */
    public function removePremis(\IntelligenceArtificielle\Entity\Regle $premiss)
    {
        $this->premiss->removeElement($premiss);
    }

    /**
     * Get premiss
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPremiss()
    {
        return $this->premiss;
    }
}