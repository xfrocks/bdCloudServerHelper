<?php

namespace Xfrocks\CloudServerHelper\Cli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Xfrocks\CloudServerHelper\Constant;

class SafeRecompilePhrases extends \XF\Cli\Command\Development\RecompilePhrases
{
    protected function configure()
    {
        $this
            ->setName('cloud:safe-recompile-phrases')
            ->setDescription('Recompiles phrases, if needed only');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define(Constant::COMPILE_PHRASE_GROUP_SKIP_SIMPLE_CACHE, true);

        $app = \XF::app();
        try {
            $addOnId = Constant::ADD_ON_ID;
            $key = Constant::COMPILE_PHRASE_GROUP_TIMESTAMP_SIMPLE_CACHE_KEY;
            $simpleCacheValue = $app->simpleCache()->getValue($addOnId, $key);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 0;
        }

        $abstractedPath = Constant::COMPILE_PHRASE_GROUP_TIMESTAMP_ABSTRACT_PATH;
        $fsValue = 0;
        try {
            $fsValue = intval($app->fs()->read($abstractedPath));
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }

        if ($fsValue < $simpleCacheValue) {
            $output->writeln("<warning>fsValue=$fsValue, simpleCacheValue=$simpleCacheValue</warning>");
            return parent::execute($input, $output);
        }

        $output->writeln("<info>fsValue=$fsValue, simpleCacheValue=$simpleCacheValue</info>");
        return 0;
    }
}
