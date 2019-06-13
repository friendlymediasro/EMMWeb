<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;


class ThemeAssetsInstallCommand extends Command
{
    const METHOD_COPY = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';

    protected static $defaultName = 'themeAssets:install';

    private $filesystem;
    private $projectDir;

    public function __construct(Filesystem $filesystem, string $projectDir = null)
    {
        parent::__construct();

        if (null === $projectDir) {
            @trigger_error(sprintf('Not passing the project directory to the constructor of %s is deprecated since Symfony 4.3 and will not be supported in 5.0.', __CLASS__), E_USER_DEPRECATED);
        }

        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Installs themes assets under a public directory')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ContainerInterface $container */
        $container = $this->getApplication()->getKernel()->getContainer();

        $io = new SymfonyStyle($input, $output);
        $io->newLine();
	    $expectedMethod = self::METHOD_COPY;
	    $io->text('Installing assets as <info>hard copies</info>.');
	    $io->newLine();

	    $rows = [];
	    $exitCode = 0;
	    $validAssetDirs = [];

	    $publicThemesDir = $this->getPublicDirectory($container).$container->getParameter('global_themes_public_dir');
	    /** @var Finder $finder */
	    $finder = Finder::create()->in($container->getParameter('global_themes_dir'))->depth(0)->directories();
	    foreach ($finder as $theme) {
		    if (!is_dir($originDir = $theme->getPathname().'/public')) {
			    continue;
		    }

		    $themeDir = $theme->getFilename();
		    $targetDir = $publicThemesDir.'/'.$themeDir.'/'.$container->getParameter('app_asset_version');
		    $validAssetDirs[] = $themeDir;

		    $message = sprintf("Theme %s/public -> %s", $theme->getFilename(), $targetDir);
		    try {
		    	//remove targetDir old files
			    $this->filesystem->remove($targetDir);
			    //copy current theme assets
			    $this->hardCopy($originDir, $targetDir);
			    $rows[] = [sprintf('<fg=green;options=bold>%s</>', '\\' === \DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" /* HEAVY CHECK MARK (U+2714) */), $message, $expectedMethod];

		    } catch (\Exception $e) {
			    $exitCode = 1;
			    $rows[] = [sprintf('<fg=red;options=bold>%s</>', '\\' === \DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" /* HEAVY BALLOT X (U+2718) */), $message, $e->getMessage()];
		    }
	    }

	    //remove not existent themes
	    $dirsToRemove = Finder::create()->depth(0)->directories()->exclude($validAssetDirs)->in($publicThemesDir);
	    $this->filesystem->remove($dirsToRemove);
	    //remove older unused versions
	    foreach ($validAssetDirs as $validAssetDir) {
		    $dirsToRemove = Finder::create()->depth(0)->directories()->exclude($container->getParameter('app_asset_version'))->in($publicThemesDir.'/'.$validAssetDir);
		    $this->filesystem->remove($dirsToRemove);
	    }

        if ($rows) {
            $io->table(['', 'Theme', 'Method / Error'], $rows);
        }

        if (0 !== $exitCode) {
            $io->error('Some errors occurred while installing assets.');
        } else {
        	$io->note('Some assets were installed via copy. If you make changes to these assets you have to run this command again.');
            $io->success($rows ? 'All assets were successfully installed.' : 'No assets were provided by any bundle.');
        }

        return $exitCode;
    }

	/**
	 * Copies origin to target.
	 * @param string $originDir
	 * @param string $targetDir
	 * @return string
	 */
    private function hardCopy(string $originDir, string $targetDir): string
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));

        return self::METHOD_COPY;
    }

    private function getPublicDirectory(ContainerInterface $container)
    {
        $defaultPublicDir = 'public';

        if (null === $this->projectDir && !$container->hasParameter('kernel.project_dir')) {
            return $defaultPublicDir;
        }

        $composerFilePath = ($this->projectDir ?? $container->getParameter('kernel.project_dir')).'/composer.json';

        if (!file_exists($composerFilePath)) {
            return $defaultPublicDir;
        }

        $composerConfig = json_decode(file_get_contents($composerFilePath), true);

        if (isset($composerConfig['extra']['public-dir'])) {
            return $composerConfig['extra']['public-dir'];
        }

        return $defaultPublicDir;
    }
}
