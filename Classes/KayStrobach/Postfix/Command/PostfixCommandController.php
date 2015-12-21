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
			->setValue('/etc/dovecot/conf.d/10-mail.conf', 'mail_home', '/var/vmail/%d/%n')             // nutzernamen aufsplitten
			->setValue('/etc/dovecot/conf.d/10-mail.conf', 'mail_location', 'maildir:~/mail:LAYOUT=fs') // prüfen am SBS
			->setValue('/etc/dovecot/conf.d/10-mail.conf', 'mail_uid', 'vmail')
			->setValue('/etc/dovecot/conf.d/10-mail.conf', 'mail_gid', 'vmail')
			->setValue('/etc/dovecot/conf.d/10-mail.conf', 'mail_privileged_group', 'vmail')
		;

		$this->outputLine('<b>Configure MySQL as Backend</b>');
		$this->dovecotConfigurationService
			->setValue('/etc/dovecot/dovecot-sql.conf.ext', 'driver', 'mysql')
			->setValue('/etc/dovecot/dovecot-sql.conf.ext', 'connect', 'host=127.0.0.1 dbname=vmail user=vmail password=vmailpasswort')
			->setValue('/etc/dovecot/dovecot-sql.conf.ext', 'default_pass_scheme', 'SHA512-CRYPT')
			->setValue('/etc/dovecot/dovecot-sql.conf.ext', 'password_query', 'SELECT username, domain, password FROM users WHERE username = \'%n\' AND domain = \'%d\'')
			->setValue('/etc/dovecot/dovecot-sql.conf.ext', 'iterate_query', 'SELECT username, domain FROM users')
		;

		$this->outputLine('<b>Configure Authentication</b>');
		$this->dovecotConfigurationService
			->setValue('/etc/dovecot/conf.d/10-auth.conf', 'disable_plaintext_auth', 'yes')
			->setValue('/etc/dovecot/conf.d/10-auth.conf', 'auth_mechanisms', 'plain login')
			->commentLine('/etc/dovecot/conf.d/10-auth.conf', 'include auth-system.conf.ext')
		;

		$this->outputLine('<b>configure dovecot basics</b>');
		$this->dovecotConfigurationService
			->setFileContentFromTemplate('/etc/dovecot/dovecot.conf')
		;

		$this->outputLine('<b>master configuration</b>');
		$this->dovecotConfigurationService
			->setSectionContentFromTemplate('/etc/dovecot/conf.d/10-master.conf', 'service auth')
		;
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
	}

	protected function shellCommand($cmd) {
		$this->outputLine('<b>' . $cmd . '</b>');
		system($cmd);
	}

}