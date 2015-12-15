<?php
namespace KayStrobach\Postfix\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Postfix".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 * @ORM\Table(name="virtual_users")
 */
class User {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne()
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * @var Domain
     */
    protected $domain;

    /**
     * @ORM\Column(name="password", length=106)
     * @var string
     */
    protected $passwort;

    /**
     * @ORM\Column(name="email", length=100)
     * @var string
     */
    protected $email;

    /**
     * Mailbox Quote in MB
     *
     * @ORM\Column(name="mailbox_limit")
     * @var int
     */
    protected $limit = 0;

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
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param mixed $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getPasswort()
    {
        return $this->passwort;
    }

    /**
     * @param string $passwort
     */
    public function setPasswort($passwort)
    {
        $this->passwort = $passwort;
    }

    /**
     * @param $password
     */
    public function setPasswortFromPlainText($password) {
        $this->passwort = "{SSHA512}".base64_encode(hash('sha512', $password . $salt, true) . $salt);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }
}
