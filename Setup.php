<?php

namespace TickTackk\RouteOnSubdomain;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;
use XF\Mvc\Entity\Repository;
use XF\Repository\Option as OptionRepo;

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

    /**
     * @param array $stateChanges
     */
    public function postInstall(array &$stateChanges) : void
    {
        $this->updateBaseUrlOptionToFallback();
    }

    /**
     * @param int|null $previousVersion
     * @param array $stateChanges
     */
    public function postUpgrade($previousVersion, array &$stateChanges) : void
    {
        if ($previousVersion < 1010070)
        {
            $this->updateBaseUrlOptionToFallback();
        }
    }

    protected function updateBaseUrlOptionToFallback() : void
    {
        $boardUrl = $this->app()->options()->boardUrl;
        $boardUrlParsed = \parse_url($boardUrl);

        $baseUrl = $boardUrl;
        if (\utf8_substr($boardUrlParsed['host'], 0, 4) === 'www.')
        {
            $baseUrl = \preg_replace(
                '/' . \preg_quote($boardUrlParsed['host']) . '/i',
                \utf8_substr($boardUrlParsed['host'], 4),
                $boardUrl
            );
        }

        $optionRepo = $this->getOptionRepo();
        $optionRepo->updateOption('tckRouteOnSubdomain_baseUrl', $baseUrl);
    }

    /**
     * @param string $identifier
     *
     * @return Repository
     */
    protected function repository(string $identifier) : Repository
    {
        return $this->app()->repository($identifier);
    }

    /**
     * @return Repository|OptionRepo
     */
    protected function getOptionRepo() : OptionRepo
    {
        return $this->repository('XF:Option');
    }
}