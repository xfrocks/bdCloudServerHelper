<?php

class bdCloudServerHelper_XenForo_Template_FileHandler extends XenForo_Template_FileHandler
{
    /**
     * @param int $date
     * @param string $title
     * @param integer $styleId
     * @param integer $languageId
     * @return string
     */
    public static function getWithDate($date, $title, $styleId, $languageId)
    {
        return self::_getDateInstance($date)->_getFileName($title, $styleId, $languageId);
    }

    /**
     * @param int $date
     * @param string $title
     * @param integer $styleId
     * @param integer $languageId
     * @param string $template
     * @return string $filename
     */
    public static function saveWithDate($date, $title, $styleId, $languageId, $template)
    {
        return self::_getDateInstance($date)->_saveTemplate($title, $styleId, $languageId,
            '<?php if (!class_exists(\'XenForo_Application\', false)) die(); ' . $template);
    }

    /**
     * @param int $date
     * @param string|array|null $title
     * @param integer|array|null $styleId
     * @param string|array|null $languageId
     */
    public static function deleteWithDate($date, $title, $styleId, $languageId)
    {
        $instance = self::_getDateInstance($date);
        $instance->_deleteTemplate($title, $styleId, $languageId);
        @unlink($instance->_path);
    }

    /**
     * @param int $date
     * @return bdCloudServerHelper_XenForo_Template_FileHandler
     */
    protected static function _getDateInstance($date)
    {
        static $instances = array();
        $date = intval($date);

        if (!isset($instances[$date])) {
            $instances[$date] = new self;
            $instances[$date]->_path .= sprintf('-%d', $date);
        }

        return $instances[$date];
    }
}