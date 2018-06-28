<?php
namespace KayStrobach\Postfix\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Postfix".   *
 *                                                                        *
 *                                                                        */

use KayStrobach\Postfix\Domain\Model\Domain;
use KayStrobach\Postfix\Domain\Model\User;
use KayStrobach\Postfix\Domain\Repository\DomainRepository;
use KayStrobach\Postfix\Domain\Repository\UserRepository;
use KayStrobach\Postfix\Service\Configuration\DovecotConfigurationService;
use KayStrobach\Postfix\Service\Configuration\PostfixConfigurationService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Utility\Files;

/**
 * @Flow\Scope("singleton")
 */
class MailserverCommandController extends \Neos\Flow\Cli\CommandController {

	/**
	 * @Flow\Inject()
	 * @var DovecotConfigurationService
	 */
	protected $dovecotConfigurationService;

	/**
	 * @Flow\Inject()
	 * @var PostfixConfigurationService
	 */
	protected $postfixConfigurationService;

	/**
	 * @Flow\Inject()
	 * @var \Neos\Flow\Configuration\ConfigurationManager
	 */
	protected $settings;

	/**
	 * @Flow\Inject()
	 * @var DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject()
	 * @var UserRepository
	 */
	protected $userRepository;

	/**
	 * install mail environment
	 */
	public function installCommand() {
		if(strpos(php_uname(), 'Debian') === false) {
			$this->outputLine('not a debian system, can´t install on that type of OS');
			throw new StopActionException('You are not running on Debian');
		}

		$this->createCertificatesCommand();
		$this->installDovecotCommand();
		$this->installPostfixCommand();
	}

	public function createCertificatesCommand() {
		$this->checkRoot();

		if(file_exists('/etc/ssl/private/mailserver.pem')) {
			$this->outputLine('<b>Certificate already in place');
			return;
		}
		$this->outputLine('Generating SSL Certificate</b>');
		$this->outputLine('   Public:  /etc/ssl/certs/mailserver.pem');
		$this->outputLine('   Private: /etc/ssl/private/mailserver.pem');
		$subject = '/C=US/ST=Oregon/L=Portland/O=IT/CN=' . gethostname();
		$cmd = 'openssl req -nodes -x509 -newkey rsa:4096 -keyout /etc/ssl/private/mailserver.pem -out /etc/ssl/certs/mailserver.pem -days 356 -subj "' . $subject . '"';
		$this->shellCommand($cmd);
	}

	/**
	 * install dovecot
	 */
	public function installDovecotCommand() {
		$this->checkRoot();

		$this->outputLine('<b>Installing needed packages for dovecot</b>');

		$this->shellCommand('DEBIAN_FRONTEND=noninteractive apt-get -y update');
		$this->shellCommand('DEBIAN_FRONTEND=noninteractive apt-get -y upgrade');
		$this->shellCommand('DEBIAN_FRONTEND=noninteractive apt-get -y install mysql-server');
		$this->shellCommand('DEBIAN_FRONTEND=noninteractive apt-get -y install dovecot-common dovecot-imapd dovecot-mysql dovecot-lmtpd');
		$this->shellCommand('DEBIAN_FRONTEND=noninteractive apt-get -y install dovecot-managesieved');

		$this->outputLine('<b>Configure Mailstorage in Filesystem</b>');
		$this->dovecotConfigurationService
			->setParam('hostname', gethostname())
			->setParam('dbuser', $this->settings->getConfiguration('Settings', 'Neos.Flow.persistence.backendOptions.user'))
			->setParam('dbpassword', $this->settings->getConfiguration('Settings', 'Neos.Flow.persistence.backendOptions.password'))
			->setParam('dbhost', $this->settings->getConfiguration('Settings', 'Neos.Flow.persistence.backendOptions.host'))
			->setParam('dbname', $this->settings->getConfiguration('Settings', 'Neos.Flow.persistence.backendOptions.dbname'))
			->setDirectoryContentFromTemplates('/etc/dovecot/');

		$this->shellCommand('mkdir -p /var/mail');
		$this->shellCommand('groupadd -g 5000 vmail');
		$this->shellCommand('useradd -m -d /var/mail -s /bin/false -g vmail vmail');
		$this->shellCommand('chown -R vmail:vmail /var/mail');

		$this->outputLine('<b>Restart dovecot</b>');
		$this->shellCommand('service dovecot restart');
	}

	/**
	 * install postfix
	 */
	public function installPostfixCommand() {
		$this->checkRoot();

		$this->outputLine('<b>Installing needed packages for postfix</b>');
		$this->shellCommand('echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections');
		$this->shellCommand('echo "postfix postfix/mailname string ' . gethostname() .'" | debconf-set-selections');

		$this->shellCommand('sudo DEBIAN_FRONTEND=noninteractive apt-get -y install postfix postfix-mysql');

		$this->outputLine('<b>Configuring Postfix</b>');
		$this->postfixConfigurationService
			->setParam('hostname', gethostname())
			->setParam('dbuser', $this->settings->getConfiguration('Settings', 'Neos.Flow.persistence.backendOptions.user'))
			->setParam('dbpassword', $this->settings->getConfiguration('Settings', 'Neos.Flow.persistence.backendOptions.password'))
			->setParam('dbhost', $this->settings->getConfiguration('Settings', 'Neos.Flow.persistence.backendOptions.host'))
			->setParam('dbname', $this->settings->getConfiguration('Settings', 'Neos.Flow.persistence.backendOptions.dbname'))
			->setFileContentFromTemplate('/etc/postfix/main.cf')
			->setFileContentFromTemplate('/etc/postfix/virtual/mysql-aliases.cf')
			->setFileContentFromTemplate('/etc/postfix/virtual/mysql-domains.cf')
			->setFileContentFromTemplate('/etc/postfix/virtual/mysql-maps.cf')
		;

		$this->outputLine('<b>Restart postfix</b>');
		$this->shellCommand('service postfix reload');
		$this->shellCommand('service postfix restart');
	}

	/**
	 * @param string $domain
	 */
	public function migrateUsernameFoldersCommand($domain) {
		$this->checkRoot();

		$this->outputLine('<b>stopping dovecot</b>');
		$this->shellCommand('service dovecot stop');
		$this->outputLine('<b>converting /var/mail</b>');
		$dirs = scandir('/var/mail');
		$filteredFolders = array();
		foreach($dirs as $dir) {
			if(strpos($dir, '.') === false) {
				$filteredFolders[] = $dir;
			}
		}

		Files::createDirectoryRecursively('/var/mail/' . $domain);

		foreach($filteredFolders as $dir) {
			$this->outputLine('<b>Moving: </b>' . $dir);
			$maildir = '/var/mail/' . $domain . '/' . substr($dir, 0, 2) . '/' . $dir;
			Files::createDirectoryRecursively($maildir);
			$this->shellCommand('mv /var/mail/' . $dir . ' ' . $maildir . '/mail');
			$this->shellCommand('rm -rf /var/mail/' . $dir);
		}

		$this->outputLine('<b>starting dovecot</b>');
		$this->shellCommand('service dovecot start');
	}

	/**
	 * Lists all domains
	 */
	public function domainsCommand() {
		$entries = array();
		/** @var Domain $entry */
		foreach($this->domainRepository->findAll() as $entry) {
			$entries[] = array(
				$entry->getId(),
				$entry->getDomain()
			);
		}
		$this->output->outputTable(
			$entries,
			array(
				'ID',
				'Domain'
			)
		);
	}

	/**
	 * list all users
	 * @param string $domain
	 */
	public function usersCommand($domain = null) {
		$entries = array();

		if($domain !== null) {
			$result = $this->userRepository->findByDomainString($domain);
		} else {
			$result = $this->userRepository->findAll();
		}

		/** @var User $entry */
		foreach($result as $entry) {
			$entries[] = array(
				$entry->getId(),
				$entry->getUsername(),
				$entry->getDomain()->getDomain()
			);
		}
		$this->output->outputTable(
			$entries,
			array(
				'ID',
				'user',
				'domain'
			)
		);
	}

	protected function shellCommand($cmd)
	{
		$this->outputLine('<b>--></b> ' . $cmd);
		system($cmd);
	}

	protected function checkRoot() {
		if(posix_getuid() !== 0) {
			$this->outputLine('<b>Must be run as root, e.g. with sudo ...</b>');
			$this->sendAndExit(99);
		}
	}
}