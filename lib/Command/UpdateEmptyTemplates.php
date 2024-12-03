<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Officeonline\Command;

use OCA\Officeonline\TemplateManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateEmptyTemplates extends Command {

	/** @var TemplateManager */
	private $templateManager;

	public function __construct(TemplateManager $templateManager) {
		$this->templateManager = $templateManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('officeonline:update-empty-templates')
			->setDescription('Update empty template files');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		try {
			$this->templateManager->updateEmptyTemplates();
			$output->writeln('<info>Empty template files were updated</info>');
		} catch (\Exception $e) {
			$output->writeln('<error>Failed to update templates</error>');
			$output->writeln($e->getMessage());
			$output->writeln($e->getTraceAsString());
		}
	}
}
