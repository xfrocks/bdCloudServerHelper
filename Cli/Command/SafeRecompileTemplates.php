<?php

namespace Xfrocks\CloudServerHelper\Cli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Xfrocks\CloudServerHelper\Constant;

class SafeRecompileTemplates extends \XF\Cli\Command\Development\RecompileTemplates
{
    protected function configure()
    {
        $this
            ->setName('cloud:safe-recompile-templates')
            ->setDescription('Recompiles parsed templates, if needed only');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = \XF::app();
        try {
            $modified = $app->container('style.masterModifiedDate');
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 0;
        }

        $abstractedPath = Constant::RECOMPILE_TEMPLATE_TIMESTAMP_ABSTRACT_PATH;
        $compiled = 0;
        try {
            $compiled = intval($app->fs()->read($abstractedPath));
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }

        if ($compiled < $modified) {
            $output->writeln("<warning>compiled=$compiled, modified=$modified</warning>");
            return parent::execute($input, $output);
        }

        $output->writeln("<info>compiled=$compiled, modified=$modified</info>");
        return 0;
    }
}
