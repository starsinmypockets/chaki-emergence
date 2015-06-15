<?php

namespace Chaki;

use Site;
use Jarvus\Sencha\Framework;

class Package extends \Jarvus\Sencha\Package
{
	// TODO: change to an instance method that only takes path and overrides a stock method
	public static function writeToDisk($path, $name, Framework $framework)
	{
		// create target directory
		mkdir($path, 0777, true);


		// get repo
		$hotfixRepoPath = Site::$rootPath . '/site-data/chaki/'.$name.'.git';

		$hotfixRepoOptions = [
		    'working_dir' => $path,
		    'debug' => true,
		    'logger' => \Emergence\Logger::getLogger()
		];
		
		if (is_dir($hotfixRepoPath)) {
		    $hotfixRepo = new \Gitonomy\Git\Repository($hotfixRepoPath, $hotfixRepoOptions);
		} else {
			$chakiData = @file_get_contents('http://chaki.io/packages/'.$name.'?format=json');
			
			if (!$chakiData || !($chakiData = @json_decode($chakiData, true))) {
				throw new \Exception("Unable to find $name on chaki");
			}

		    $hotfixRepo = \Gitonomy\Git\Admin::cloneTo($hotfixRepoPath, 'https://github.com/'.$chakiData['data']['GitHubPath'].'.git', true, $hotfixRepoOptions);
		}


		// choose best hotfixes branch
		$hotfixReferences = $hotfixRepo->getReferences();

		$hotfixBranch = null;
		$hotfixVersionStack = explode('.', $framework->getVersion());

		while (
		    count($hotfixVersionStack) &&
		    ($hotfixVersionBranchName = $framework->getName() . '/' . implode('/', $hotfixVersionStack)) &&
		    !$hotfixReferences->hasBranch($hotfixVersionBranchName)
		) {
		    array_pop($hotfixVersionStack);
		    $hotfixVersionBranchName = null;
		}

		if (!$hotfixVersionBranchName && $hotfixReferences->hasBranch($framework->getName())) {
		    $hotfixVersionBranchName = $framework->getName();
		}

		if (!$hotfixVersionBranchName) {
		    $hotfixVersionBranchName = 'master';
		}

		$hotfixVersionBranch = $hotfixReferences->getBranch($hotfixVersionBranchName);


		// checkout branch
		$hotfixRepo->run('checkout', ['-f', $hotfixVersionBranchName]);


		return $hotfixVersionBranchName;
	}
}