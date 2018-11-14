<?php

namespace Xfrocks\CloudServerHelper\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Xfrocks\CloudServerHelper\Constant;

class SafeRebuildNavCache extends Command
{
    protected function configure()
    {
        $this
            ->setName('cloud:safe-rebuild-nav-cache')
            ->setDescription('Rebuild navigation cache if needed');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define(Constant::REBUILD_NAV_CACHE_SKIP_SIMPLE_CACHE, true);

        $app = \XF::app();
        try {
            $addOnId = Constant::ADD_ON_ID;
            $key = Constant::REBUILD_NAV_CACHE_TIMESTAMP_SIMPLE_CACHE_KEY;
            $rebuilt = $app->simpleCache()->getValue($addOnId, $key);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 0;
        }

        $cacheFile = 'code-cache://' . $app->container('navigation.file');
        $timestamp = 0;
        try {
            $timestamp = $app->fs()->getTimestamp($cacheFile);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }

        if ($timestamp < $rebuilt) {
            $output->writeln("<warning>timestamp=$timestamp, rebuilt=$rebuilt</warning>");

            /** @var \XF\Repository\Navigation $navigationRepo */
            $navigationRepo = $app->repository('XF:Navigation');
            $navigationRepo->rebuildNavigationCache();
            return 0;
        }

        $output->writeln("<info>timestamp=$timestamp, rebuilt=$rebuilt</info>");
        return 0;
    }
}
