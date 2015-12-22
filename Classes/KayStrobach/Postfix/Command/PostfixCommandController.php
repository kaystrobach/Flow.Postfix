<?php
namespace KayStrobach\Postfix\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "KayStrobach.Postfix".   *
 *                                                                        *
 *                                                                        */

use KayStrobach\Postfix\Service\Configuration\DovecotConfigurationService;
use KayStrobach\Postfix\Service\Configuration\PostfixConfigurationService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Exception\StopActionException;

/**
 * @Flow\Scope("singleton")
 */
class PostfixCommandController extends \TYPO3\Flow\Cli\CommandController {

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
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $settings;

	/**
	 * install mail environment
	 */
	public function installCommand() {
		if(strpos(php_uname(), 'Debian') === false) {
			$this->outputLine('not a debian system, can´t install on that type of OS');
			throw new StopActionException('You are not running on Debian');
		}

		$this->installDovecotCommand();
		$this->installPostfixCommand();
	}

	/**
	 * install dovecot
	 */
	public function installDovecotCommand() {
		$this->outputLine('<b>Installing needed packages for dovecot</b>');

		$this->shellCommand('sudo DEBIAN_FRONTEND=noninteractive apt-get -y update');
		$this->shellCommand('sudo DEBIAN_FRONTEND=noninteractive apt-get -y upgrade');
		$this->shellCommand('sudo DEBIAN_FRONTEND=noninteractive apt-get -y install mysql-server');
		$this->shellCommand('sudo DEBIAN_FRONTEND=noninteractive apt-get -y install dovecot-common dovecot-imapd dovecot-mysql dovecot-lmtpd');

		$this->outputLine('<b>Configure Mailstorage in Filesystem</b>');
		$this->dovecotConfigurationService
			->setParam('hostname', gethostname())
			->setParam('dbuser', $this->settings->getConfiguration('Settings', 'TYPO3.Flow.persistence.backendOptions.user'))
			->setParam('dbpassword', $this->settings->getConfiguration('Settings', 'TYPO3.Flow.persistence.backendOptions.password'))
			->setParam('dbhost', $this->settings->getConfiguration('Settings', 'TYPO3.Flow.persistence.backendOptions.host'))
			->setParam('dbname', $this->settings->getConfiguration('Settings', 'TYPO3.Flow.persistence.backendOptions.dbname'))
			->setDirectoryContentFromTemplates('/etc/dovecot/');

		$this->outputLine('<b>Restart dovecot</b>');
		$this->shellCommand('service dovecot restart');
	}

	/**
	 * install postfix
	 */
	public function installPostfixCommand() {
		$this->outputLine('<b>Installing needed packages for postfix</b>');
		$this->shellCommand('debconf-set-selections <<< "postfix postfix/mailname string ' . gethostname() . '"');
		$this->shellCommand('debconf-set-selections <<< "postfix postfix/main_mailer_type string \'Internet Site\'""');
		$this->shellCommand('sudo DEBIAN_FRONTEND=noninteractive apt-get -y install postfix postfix-mysql');

		$this->outputLine('<b>Configuring Postfix</b>');
		$this->postfixConfigurationService
			->setParam('hostname', gethostname())
			->setParam('dbuser', $this->settings->getConfiguration('Settings', 'TYPO3.Flow.persistence.backendOptions.user'))
			->setParam('dbpassword', $this->settings->getConfiguration('Settings', 'TYPO3.Flow.persistence.backendOptions.password'))
			->setParam('dbhost', $this->settings->getConfiguration('Settings', 'TYPO3.Flow.persistence.backendOptions.host'))
			->setParam('dbname', $this->settings->getConfiguration('Settings', 'TYPO3.Flow.persistence.backendOptions.dbname'))
			->setFileContentFromTemplate('/etc/postfix/main.cf')
			->setFileContentFromTemplate('/etc/postfix/virtual/mysql-aliases.cf')
			->setFileContentFromTemplate('/etc/postfix/virtual/mysql-domains.cf')
			->setFileContentFromTemplate('/etc/postfix/virtual/mysql-maps.cf')
		;

		$this->outputLine('<b>Restart postfix</b>');
		$this->shellCommand('service postfix reload');
		$this->shellCommand('service postfix restart');
	}

	public function checkIteratorCommand() {
		$this->dovecotConfigurationService->setDirectoryContentFromTemplates('/etc/dovecot/');
	}

	protected function shellCommand($cmd)
	{
		$this->outputLine('<b>' . $cmd . '</b>');
		system($cmd);
	}

}