<?php

class bdCloudServerHelper_Deferred_DataRegistryOptionsDump extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $php = self::generatePhp();
        $path = sprintf('%s%s%s.php', XenForo_Helper_File::getInternalDataPath(),
            DIRECTORY_SEPARATOR, __CLASS__);
        file_put_contents($path, $php);

        return false;
    }

    public static function generatePhp()
    {
        /** @var XenForo_Model_Option $optionsModel */
        $optionsModel = XenForo_Model::create('XenForo_Model_Option');
        $dump = serialize($optionsModel->buildOptionArray());

        $php = sprintf("<?php\n\n\$data = %s;", var_export($dump, true));

        return $php;
    }
}