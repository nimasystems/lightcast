<?php

class tProjectUpdates extends lcTaskController
{
    public function executeTask()
    {
        $action = $this->getRequest()->getParam('action');

        switch ($action) {
            case 'update_core_config':
                return $this->updateCoreConfig();

            default:
                $this->display($this->getHelpInfo(), false);
                return true;
        }
    }

    private function updateCoreConfig()
    {


        return true;
    }

    public function getHelpInfo()
    {
        $help =
            lcConsolePainter::formatColoredConsoleText('System Updates', 'green') . "\n" .
            lcConsolePainter::formatColoredConsoleText('--------------------', 'green') . "\n\n" .
            'update_core_config - Updates all core config declarations to the new deep style.' . "\n";

        return $help;
    }
}