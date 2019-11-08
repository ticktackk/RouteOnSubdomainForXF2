<?php

namespace TickTackk\RouteOnSubdomain;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;

/**
 * Class Setup
 *
 * @package TickTackk\RouteOnSubdomain
 */
class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1() : void
    {
        $sm = $this->schemaManager();

        $sm->createTable('xf_tck_route_on_subdomain', function (Create $table)
        {
            $table->addColumn('route_prefix', 'varbinary', 50);
            $table->addColumn('is_on_subdomain', 'tinyint')->setDefault(0);

            $table->addUniqueKey('route_prefix');
        });
    }

    public function uninstallStep1() : void
    {
        $sm = $this->schemaManager();

        $sm->dropTable('xf_tck_route_on_subdomain');
    }
}