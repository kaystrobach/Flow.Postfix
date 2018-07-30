<?php
namespace KayStrobach\Postfix\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Postfix".   *
 *                                                                        *
 *                                                                        */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 * @ORM\Table(name="mailserver_domains")
 */
class Domain {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="domain", length=50)
     * @var string
     */
    protected $domain;

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain) {
        $this->domain= $domain;
    }

    /**
     * @return string
     */
    public function getDomain() {
        return $this->domain;
    }
}